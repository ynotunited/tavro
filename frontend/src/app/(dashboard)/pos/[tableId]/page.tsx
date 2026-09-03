'use client';

import { useState, useEffect, useRef } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { sanitizeString } from '@/lib/sanitize';
import { haptics } from '@/lib/haptics';
import {
  Drawer,
  DrawerClose,
  DrawerContent,
  DrawerDescription,
  DrawerFooter,
  DrawerHeader,
  DrawerTitle,
} from '@/components/ui/drawer';
import { motion, AnimatePresence } from 'framer-motion';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Category { id: number; name: string; color: string; }
interface Product {
  id: number; name: string; selling_price: string;
  category_id: number; is_available: boolean; type: string;
}
interface OrderItem {
  id: number; product_name: string; variant_name: string | null;
  unit_price: string; quantity: number; subtotal: string;
  notes: string | null; serve_notes: string | null; status: string; voided_at: string | null;
}
interface Order {
  id: number; order_number: string; status: string;
  subtotal: string; tax_amount: string; service_charge_amount: string;
  discount_amount: string; total_amount: string;
  items: OrderItem[];
}

// ─── Sub-components ───────────────────────────────────────────────────────────

function OrderLineItem({ item, onQtyChange, onVoid }: {
  item: OrderItem;
  onQtyChange: (id: number, delta: number) => void;
  onVoid: (id: number) => void;
}) {
  const [showVoidReason, setShowVoidReason] = useState(false);
  const [voidReason, setVoidReason] = useState('');
  const [isDragging, setIsDragging] = useState(false);

  return (
    <div className="relative border-b border-white/5 group">
      {/* Background Delete Action */}
      <div className="absolute inset-0 bg-red-500/20 flex items-center justify-end px-4 z-0">
        <span className="text-red-500 text-xs font-bold tracking-wider">SWIPE TO VOID</span>
      </div>

      {/* Foreground Draggable Item */}
      <motion.div 
        drag="x"
        dragConstraints={{ left: -100, right: 0 }}
        dragElastic={0.1}
        onDragStart={() => setIsDragging(true)}
        onDragEnd={(e, { offset, velocity }) => {
          setTimeout(() => setIsDragging(false), 100);
          if (offset.x < -75 || velocity.x < -500) {
            haptics.heavy();
            setShowVoidReason(true);
          }
        }}
        className="relative z-10 bg-[#0F172A] flex items-center gap-3 py-3 w-full touch-pan-y"
      >
        <div className="flex-1 min-w-0 pointer-events-none">
          <p className="text-sm font-medium text-white leading-tight truncate">
            {item.product_name}
            {item.variant_name && <span className="text-white/40 ml-1">({item.variant_name})</span>}
          </p>
          {item.notes && <p className="text-xs text-white/30 mt-0.5 truncate">📝 {item.notes}</p>}
          {item.serve_notes && <p className="text-xs text-amber-300/80 mt-0.5 truncate">🍹 Serve: {item.serve_notes}</p>}
        </div>

        {/* Qty Controls */}
        <div className="flex items-center gap-2 shrink-0 relative z-20">
          <button
            onClick={(e) => {
              if (isDragging) return;
              haptics.light();
              onQtyChange(item.id, -1);
            }}
            className="w-10 h-10 rounded-full border border-white/20 text-white/60 flex items-center justify-center hover:border-white/50 hover:text-white transition-colors text-xl leading-none"
          >−</button>
          <span className="w-8 text-center font-mono text-white text-lg pointer-events-none">{item.quantity}</span>
          <button
            onClick={(e) => {
              if (isDragging) return;
              haptics.light();
              onQtyChange(item.id, 1);
            }}
            className="w-10 h-10 rounded-full border border-white/20 text-white/60 flex items-center justify-center hover:border-white/50 hover:text-white transition-colors text-xl leading-none"
          >+</button>
        </div>

        {/* Price */}
        <p className="font-mono text-sm text-amber-400 w-20 text-right shrink-0 pointer-events-none">
          ₦{Number(item.subtotal).toLocaleString()}
        </p>

        {/* Void Button (Desktop Fallback) */}
        <button
          onClick={(e) => {
            if (isDragging) return;
            setShowVoidReason(true);
          }}
          className="opacity-0 group-hover:opacity-100 text-red-500 text-xs shrink-0 transition-opacity hidden md:block relative z-20"
        >✕</button>
      </motion.div>

      <Drawer open={showVoidReason} onOpenChange={setShowVoidReason}>
        <DrawerContent className="bg-[#1E293B] border-white/10 text-white z-[60]">
          <DrawerHeader>
            <DrawerTitle>Void Item</DrawerTitle>
            <DrawerDescription>Why are you removing this item?</DrawerDescription>
          </DrawerHeader>
          <div className="p-4 space-y-4">
            <input
              value={voidReason}
              onChange={e => setVoidReason(e.target.value)}
              placeholder="e.g. Customer changed mind"
              className="w-full bg-white/5 border border-white/10 text-white px-4 py-4 rounded outline-none focus:border-red-500/50 h-14"
            />
          </div>
          <DrawerFooter className="flex-row gap-3 pt-0">
            <DrawerClose asChild>
              <button className="flex-1 py-4 border border-white/10 text-white/60 rounded text-base font-medium h-14">Cancel</button>
            </DrawerClose>
            <button 
              onClick={() => {
                haptics.heavy();
                onVoid(item.id); 
                setShowVoidReason(false); 
              }} 
              className="flex-1 py-4 bg-red-500 text-white font-bold rounded text-base h-14"
            >
              Void Item
            </button>
          </DrawerFooter>
        </DrawerContent>
      </Drawer>
    </div>
  );
}

// ─── Main POS Screen ─────────────────────────────────────────────────────────

export default function POSOrderScreen({ params }: { params: { tableId: string } }) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const queryClient = useQueryClient();
  const [orderId, setOrderId] = useState<number | null>(
    searchParams.get('orderId') ? Number(searchParams.get('orderId')) : null
  );
  const [activeCategoryId, setActiveCategoryId] = useState<number | null>(null);
  const [search, setSearch] = useState('');
  const [showDiscount, setShowDiscount] = useState(false);
  const [discountType, setDiscountType] = useState<'percent' | 'flat'>('percent');
  const [discountValue, setDiscountValue] = useState('');
  const searchRef = useRef<HTMLInputElement>(null);

  // ─── Queries ──────────────────────────────────────────────────────────────

  const { data: categories = [] } = useQuery<Category[]>({
    queryKey: ['categories'],
    queryFn: async () => (await api.get('/categories')).data.data,
  });

  const { data: products = [] } = useQuery<Product[]>({
    queryKey: ['products'],
    queryFn: async () => (await api.get('/products')).data.data,
  });

  const { data: order, refetch: refetchOrder } = useQuery<Order>({
    queryKey: ['order', orderId],
    queryFn: async () => (await api.get(`/orders/${orderId}`)).data.data,
    enabled: !!orderId,
    refetchInterval: 5000,
  });

  // ─── Mutations ────────────────────────────────────────────────────────────

  const addItemMutation = useMutation({
    mutationFn: async (productId: number) => {
      if (!orderId) return;
      return api.post(`/orders/${orderId}/items`, { product_id: productId, quantity: 1 });
    },
    onSuccess: () => refetchOrder(),
  });

  const updateQtyMutation = useMutation({
    mutationFn: async ({ itemId, qty }: { itemId: number; qty: number }) =>
      api.patch(`/orders/${orderId}/items/${itemId}`, { quantity: qty }),
    onSuccess: () => refetchOrder(),
  });

  const voidItemMutation = useMutation({
    mutationFn: async (itemId: number) =>
      api.post(`/orders/${orderId}/items/${itemId}/void`, { void_reason: 'Voided by staff' }),
    onSuccess: () => refetchOrder(),
  });

  const sendMutation = useMutation({
    mutationFn: async () => api.post(`/orders/${orderId}/send`),
    onSuccess: () => refetchOrder(),
  });

  const discountMutation = useMutation({
    mutationFn: async () => {
      const val = parseFloat(discountValue);
      if (discountType === 'percent' && (val <= 0 || val > 100)) {
        throw new Error('Percentage must be between 1 and 100');
      }
      if (val <= 0) {
        throw new Error('Discount value must be greater than 0');
      }
      return api.post(`/orders/${orderId}/discount`, {
        discount_type: discountType,
        discount_value: val,
      });
    },
    onSuccess: () => { refetchOrder(); setShowDiscount(false); },
  });

  // ─── Derived State ────────────────────────────────────────────────────────

  const filteredProducts = products.filter(p => {
    if (!p.is_available) return false;
    if (activeCategoryId && p.category_id !== activeCategoryId) return false;
    if (search && !p.name.toLowerCase().includes(search.toLowerCase())) return false;
    return true;
  });

  const handleQtyChange = (itemId: number, delta: number) => {
    const item = order?.items.find(i => i.id === itemId);
    if (!item) return;
    const newQty = item.quantity + delta;
    if (newQty <= 0) return;
    updateQtyMutation.mutate({ itemId, qty: newQty });
  };

  const activeItems = order?.items.filter(i => !i.voided_at) ?? [];
  const isSent = ['SENT', 'PREPARING', 'READY', 'SERVED'].includes(order?.status ?? '');

  return (
    <div className="h-screen bg-[#0F172A] text-white flex flex-col overflow-hidden">
      
      {/* ── Header ─────────────────────────────────────────────────────── */}
      <div className="flex items-center justify-between px-4 py-3 border-b border-white/10 shrink-0">
        <div className="flex items-center gap-3">
          <button onClick={() => router.push('/pos')} className="text-white/40 hover:text-white text-xl transition-colors">←</button>
          <div>
            <p className="font-bold text-sm">Table {params.tableId}</p>
            {order && (
              <p className="text-xs text-white/40 font-mono">{order.order_number}</p>
            )}
          </div>
        </div>
        <div className="flex items-center gap-2">
          {order && (
            <span className={`text-xs px-2 py-0.5 rounded font-medium ${
              order.status === 'SENT' ? 'bg-amber-400/20 text-amber-400' :
              order.status === 'OPEN' ? 'bg-emerald-500/20 text-emerald-400' :
              'bg-white/10 text-white/40'
            }`}>
              {order.status}
            </span>
          )}
          <button onClick={() => setShowDiscount(true)} className="text-xs text-white/40 hover:text-white border border-white/10 px-2 py-1 rounded transition-colors">
            % Disc
          </button>
        </div>
      </div>

      {/* ── Main Split Layout ──────────────────────────────────────────── */}
      <div className="flex-1 flex flex-col md:flex-row overflow-hidden">

        {/* ── TOP / LEFT: Order Items Panel ─────────────────────────── */}
        <div className="flex flex-col md:w-80 md:border-r md:border-white/10 border-b border-white/10 overflow-hidden" style={{ maxHeight: '45vh' }}>
          
          {/* Items list */}
          <div className="flex-1 overflow-y-auto px-4 relative">
            {!order || activeItems.length === 0 ? (
              <div className="flex flex-col items-center justify-center h-full py-8 text-white/20 text-center">
                <p className="text-3xl mb-2">🛒</p>
                <p className="text-sm">Tap products below to add</p>
              </div>
            ) : (
              activeItems.map(item => (
                <OrderLineItem
                  key={item.id}
                  item={item}
                  onQtyChange={handleQtyChange}
                  onVoid={(id) => voidItemMutation.mutate(id)}
                />
              ))
            )}
          </div>

          {/* Totals + Send Button */}
          <div className="border-t border-white/10 px-4 py-3 shrink-0 space-y-1">
            {order && (
              <>
                <div className="flex justify-between text-xs text-white/40">
                  <span>Subtotal</span>
                  <span className="font-mono">₦{Number(order.subtotal).toLocaleString()}</span>
                </div>
                {Number(order.tax_amount) > 0 && (
                  <div className="flex justify-between text-xs text-white/40">
                    <span>Tax</span>
                    <span className="font-mono">₦{Number(order.tax_amount).toLocaleString()}</span>
                  </div>
                )}
                {Number(order.service_charge_amount) > 0 && (
                  <div className="flex justify-between text-xs text-white/40">
                    <span>Service Charge</span>
                    <span className="font-mono">₦{Number(order.service_charge_amount).toLocaleString()}</span>
                  </div>
                )}
                {Number(order.discount_amount) > 0 && (
                  <div className="flex justify-between text-xs text-emerald-400">
                    <span>Discount</span>
                    <span className="font-mono">−₦{Number(order.discount_amount).toLocaleString()}</span>
                  </div>
                )}
                <div className="flex justify-between pt-1 border-t border-white/10">
                  <span className="text-sm font-bold">TOTAL</span>
                  <span className="font-mono text-lg font-bold text-amber-400">
                    ₦{Number(order.total_amount).toLocaleString()}
                  </span>
                </div>
              </>
            )}

            <button
              onClick={() => {
                haptics.heavy();
                sendMutation.mutate();
              }}
              disabled={!order || activeItems.length === 0 || sendMutation.isPending || isSent}
              className="w-full mt-2 bg-amber-400 text-[#0F172A] font-bold py-4 text-sm tracking-wider disabled:opacity-40 active:scale-[0.98] transition-all rounded-md h-14"
            >
              {sendMutation.isPending ? 'SENDING...' : isSent ? 'ORDER SENT ✓' : 'SEND TO KITCHEN'}
            </button>
            {order && (
              <button
                onClick={() => {
                  haptics.heavy();
                  router.push(`/pos/${params.tableId}/payment/${order.id}`);
                }}
                className="w-full mt-2 bg-emerald-500 text-white font-bold py-4 text-sm tracking-wider active:scale-[0.98] transition-all hover:bg-emerald-400 rounded-md h-14"
              >
                PAY — ₦{Number(order.total_amount).toLocaleString()}
              </button>
            )}
          </div>
        </div>

        {/* ── BOTTOM / RIGHT: Product Browser ────────────────────────── */}
        <div className="flex-1 flex flex-col overflow-hidden">

          {/* Search */}
          <div className="px-4 py-2 border-b border-white/10 shrink-0">
            <input
              ref={searchRef}
              value={search}
              onChange={e => setSearch(e.target.value)}
              placeholder="🔍  Search products..."
              className="w-full bg-white/5 border border-white/10 rounded text-sm text-white placeholder-white/25 px-3 py-2.5 outline-none focus:border-amber-400/50 transition-colors"
            />
          </div>

          {/* Category Tabs */}
          <div className="flex overflow-x-auto gap-2 px-4 py-2 border-b border-white/10 shrink-0 scrollbar-none">
            <button
              onClick={() => setActiveCategoryId(null)}
              className={`px-3 py-1.5 rounded text-xs font-medium whitespace-nowrap transition-colors ${
                !activeCategoryId ? 'bg-amber-400 text-[#0F172A]' : 'bg-white/5 text-white/60 hover:bg-white/10'
              }`}
            >All</button>
            {categories.map(cat => (
              <button
                key={cat.id}
                onClick={() => setActiveCategoryId(cat.id === activeCategoryId ? null : cat.id)}
                className={`px-3 py-1.5 rounded text-xs font-medium whitespace-nowrap transition-colors ${
                  activeCategoryId === cat.id ? 'bg-amber-400 text-[#0F172A]' : 'bg-white/5 text-white/60 hover:bg-white/10'
                }`}
              >{cat.name}</button>
            ))}
          </div>

          {/* Product Grid */}
          <div className="flex-1 overflow-y-auto p-3">
            <div className="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
              {filteredProducts.map(product => (
                <button
                  key={product.id}
                  onClick={() => {
                    haptics.light();
                    addItemMutation.mutate(product.id);
                  }}
                  disabled={addItemMutation.isPending}
                  className="flex flex-col items-start p-3 bg-white/5 border border-white/10 rounded-lg hover:bg-white/10 hover:border-amber-400/40 active:scale-95 transition-all text-left"
                >
                  <span className="text-xs font-medium text-white leading-tight line-clamp-2 mb-2">
                    {product.name}
                  </span>
                  <span className="text-amber-400 font-mono text-xs font-bold mt-auto">
                    ₦{Number(product.selling_price).toLocaleString()}
                  </span>
                </button>
              ))}
              {filteredProducts.length === 0 && (
                <div className="col-span-full text-center py-12 text-white/20">
                  No products found
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* ── Discount Drawer ─────────────────────────────────────────────── */}
      <Drawer open={showDiscount} onOpenChange={setShowDiscount}>
        <DrawerContent className="bg-[#1E293B] border-white/10 text-white">
          <DrawerHeader>
            <DrawerTitle>Apply Discount</DrawerTitle>
            <DrawerDescription>Apply a percentage or flat discount to the order.</DrawerDescription>
          </DrawerHeader>
          <div className="p-4 space-y-4">
            <div className="flex gap-2">
              {(['percent', 'flat'] as const).map(type => (
                <button
                  key={type}
                  onClick={() => {
                    haptics.light();
                    setDiscountType(type);
                  }}
                  className={`flex-1 py-3 text-sm rounded transition-colors ${
                    discountType === type ? 'bg-amber-400 text-[#0F172A] font-bold' : 'bg-white/5 text-white/60'
                  }`}
                >
                  {type === 'percent' ? 'Percentage (%)' : 'Flat (₦)'}
                </button>
              ))}
            </div>
            <input
              type="number"
              value={discountValue}
              onChange={e => setDiscountValue(e.target.value)}
              placeholder={discountType === 'percent' ? 'e.g. 10 (max 100)' : 'e.g. 5000'}
              min="0"
              max={discountType === 'percent' ? '100' : '999999'}
              className="w-full bg-white/5 border border-white/10 text-white px-4 py-4 rounded outline-none focus:border-amber-400/50 text-center text-2xl font-mono h-14"
            />
          </div>
          <DrawerFooter className="flex-row gap-3 pt-0">
            <DrawerClose asChild>
              <button className="flex-1 py-4 border border-white/10 text-white/60 rounded text-base font-medium h-14">Cancel</button>
            </DrawerClose>
            <button 
              onClick={() => {
                haptics.heavy();
                discountMutation.mutate();
              }} 
              className="flex-1 py-4 bg-amber-400 text-[#0F172A] font-bold rounded text-base h-14"
            >
              Apply
            </button>
          </DrawerFooter>
        </DrawerContent>
      </Drawer>
    </div>
  );
}
