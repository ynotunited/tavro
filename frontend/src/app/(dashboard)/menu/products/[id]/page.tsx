'use client';

import { useState, useEffect } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { trimStrings } from '@/lib/sanitize';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Card, CardContent } from '@/components/ui/Card';

interface Category {
  id: number;
  name: string;
}

interface Product {
  id: number;
  name: string;
  sku: string | null;
  category_id: number | null;
  description: string | null;
  type: string | null;
  selling_price: string | number | null;
  cost_price: string | number | null;
  is_taxable: boolean;
  has_service_charge: boolean;
  is_available: boolean;
  track_inventory: boolean;
  variants?: Variant[];
  recipe?: { items: RecipeItem[] } | null;
}

interface Variant {
  id?: number;
  name: string;
  selling_price: string | number;
  is_available: boolean;
}

interface RecipeItem {
  ingredient_name: string;
  quantity: string | number;
  unit: string;
}

interface ModifierGroup {
  id: number;
  name: string;
  modifiers?: { name: string }[];
}

const EMPTY_FORM = {
  name: '',
  sku: '',
  category_id: '',
  description: '',
  type: 'food',
  selling_price: '',
  cost_price: '',
  is_taxable: true,
  has_service_charge: true,
  is_available: true,
  track_inventory: false,
};

export default function ProductBuilderPage() {
  const params = useParams<{ id: string }>();
  const productId = params?.id ?? '';
  const isNew = productId === 'new';
  const router = useRouter();
  const queryClient = useQueryClient();

  const [activeTab, setActiveTab] = useState<'basic' | 'variants' | 'modifiers' | 'recipe'>('basic');
  const [formData, setFormData] = useState(EMPTY_FORM);

  const { data: categories = [] } = useQuery<Category[]>({
    queryKey: ['categories'],
    queryFn: async () => (await api.get('/categories')).data.data,
  });

  const { data: product } = useQuery<Product>({
    queryKey: ['products', productId],
    queryFn: async () => (await api.get(`/products/${productId}`)).data.data,
    enabled: !isNew,
  });

  useEffect(() => {
    if (product) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setFormData({
        name: product.name || '',
        sku: product.sku || '',
        category_id: product.category_id ? String(product.category_id) : '',
        description: product.description || '',
        type: product.type || 'food',
        selling_price: product.selling_price != null ? String(product.selling_price) : '',
        cost_price: product.cost_price != null ? String(product.cost_price) : '',
        is_taxable: product.is_taxable,
        has_service_charge: product.has_service_charge,
        is_available: product.is_available,
        track_inventory: product.track_inventory,
      });
    }
  }, [product]);

  const saveProductMutation = useMutation({
    mutationFn: async (data: typeof EMPTY_FORM) => {
      if (isNew) return api.post('/products', data);
      return api.patch(`/products/${productId}`, data);
    },
    onSuccess: (res) => {
      queryClient.invalidateQueries({ queryKey: ['products'] });
      if (isNew) {
        router.push(`/menu/products/${res.data.data.id}`);
      } else {
        alert('Product saved successfully');
      }
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    saveProductMutation.mutate(trimStrings(formData));
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <button onClick={() => router.push('/menu')} className="text-gray-400 hover:text-charcoal">
          ← Back
        </button>
        <h1 className="text-2xl font-bold text-charcoal">
          {isNew ? 'New Product' : product?.name || 'Edit Product'}
        </h1>
      </div>

      <div className="flex gap-4 border-b border-gray-200">
        {(['basic', 'variants', 'modifiers', 'recipe'] as const).map((tab) => (
          <button
            key={tab}
            onClick={() => setActiveTab(tab)}
            disabled={isNew && tab !== 'basic'}
            className={`pb-2 px-1 text-sm font-medium ${
              activeTab === tab
                ? 'border-b-2 border-amber text-charcoal'
                : 'text-gray-500 hover:text-gray-700'
            } ${(isNew && tab !== 'basic') ? 'opacity-50 cursor-not-allowed' : ''}`}
          >
            {tab.charAt(0).toUpperCase() + tab.slice(1)}
          </button>
        ))}
      </div>

      {activeTab === 'basic' && (
        <form onSubmit={handleSubmit} className="space-y-6">
          <Card padding="md">
            <CardContent className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium mb-1">Product Name *</label>
                  <Input
                    required
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">Category</label>
                  <select
                    className="w-full px-3 py-2 border border-gray-300 focus:outline-none focus:ring-1 focus:ring-amber text-sm"
                    value={formData.category_id}
                    onChange={(e) => setFormData({ ...formData, category_id: e.target.value })}
                  >
                    <option value="">No Category</option>
                    {categories.map((c) => (
                      <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">Selling Price (₦) *</label>
                  <Input
                    required
                    type="number"
                    step="0.01"
                    value={formData.selling_price}
                    onChange={(e) => setFormData({ ...formData, selling_price: e.target.value })}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">Cost Price (₦)</label>
                  <Input
                    type="number"
                    step="0.01"
                    value={formData.cost_price}
                    onChange={(e) => setFormData({ ...formData, cost_price: e.target.value })}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">Type</label>
                  <select
                    className="w-full px-3 py-2 border border-gray-300 focus:outline-none focus:ring-1 focus:ring-amber text-sm"
                    value={formData.type}
                    onChange={(e) => setFormData({ ...formData, type: e.target.value })}
                  >
                    <option value="food">Food</option>
                    <option value="drink">Drink</option>
                    <option value="cocktail">Cocktail</option>
                    <option value="modifier">Modifier</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">SKU</label>
                  <Input
                    value={formData.sku}
                    onChange={(e) => setFormData({ ...formData, sku: e.target.value })}
                  />
                </div>
              </div>

              <div className="flex gap-4 pt-4 border-t border-gray-100">
                <label className="flex items-center gap-2 text-sm">
                  <input
                    type="checkbox"
                    checked={formData.is_available}
                    onChange={(e) => setFormData({ ...formData, is_available: e.target.checked })}
                  />
                  Available
                </label>
                <label className="flex items-center gap-2 text-sm">
                  <input
                    type="checkbox"
                    checked={formData.is_taxable}
                    onChange={(e) => setFormData({ ...formData, is_taxable: e.target.checked })}
                  />
                  Taxable
                </label>
              </div>

              <div className="pt-4 flex justify-end">
                <Button type="submit" disabled={saveProductMutation.isPending}>
                  {saveProductMutation.isPending ? 'Saving...' : 'Save Basic Details'}
                </Button>
              </div>
            </CardContent>
          </Card>
        </form>
      )}

      {activeTab === 'variants' && !isNew && product && (
        <VariantsManager product={product} />
      )}
      
      {activeTab === 'modifiers' && !isNew && (
         <ModifierManager />
      )}

      {activeTab === 'recipe' && !isNew && product && (
         <RecipeManager product={product} />
      )}
    </div>
  );
}

// ─── Sub-components for Variants and Recipes ────────────────────────────────

function VariantsManager({ product }: { product: Product }) {
  const queryClient = useQueryClient();
  const [variants, setVariants] = useState<Variant[]>(product.variants || []);
  
  const saveMutation = useMutation({
    mutationFn: async (data: Variant[]) => api.post(`/products/${product.id}/variants`, { variants: data }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['products', product.id.toString()] });
      alert('Variants saved');
    }
  });

  const addVariant = () => setVariants([...variants, { name: '', selling_price: '', is_available: true }]);
  
  return (
    <Card padding="md">
      <CardContent className="space-y-4">
        {variants.map((v, i) => (
          <div key={i} className="flex gap-4 items-end bg-gray-50 p-4 border border-gray-200">
            <div className="flex-1">
              <label className="block text-xs mb-1">Name</label>
              <Input value={v.name} onChange={(e) => {
                const newV = [...variants]; newV[i].name = e.target.value; setVariants(newV);
              }} />
            </div>
            <div className="w-32">
              <label className="block text-xs mb-1">Price (₦)</label>
              <Input type="number" value={v.selling_price} onChange={(e) => {
                const newV = [...variants]; newV[i].selling_price = e.target.value; setVariants(newV);
              }} />
            </div>
            <button onClick={() => setVariants(variants.filter((_, idx) => idx !== i))} className="text-red-500 text-sm pb-2 hover:underline">
              Remove
            </button>
          </div>
        ))}
        <Button variant="secondary" onClick={addVariant} className="w-full text-sm py-2">
          + Add Variant
        </Button>
        <div className="flex justify-end pt-4">
          <Button onClick={() => saveMutation.mutate(variants)} disabled={saveMutation.isPending}>
            Save Variants
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}

function RecipeManager({ product }: { product: Product }) {
  const queryClient = useQueryClient();
  const [items, setItems] = useState<RecipeItem[]>(product.recipe?.items || []);

  const saveMutation = useMutation({
    mutationFn: async (data: RecipeItem[]) => api.post(`/products/${product.id}/recipe`, { items: data }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['products', product.id.toString()] });
      alert('Recipe saved');
    }
  });

  const addItem = () => setItems([...items, { ingredient_name: '', quantity: '', unit: 'unit' }]);

  return (
    <Card padding="md">
      <CardContent className="space-y-4">
        {items.map((item, i) => (
          <div key={i} className="flex gap-4 items-end bg-gray-50 p-4 border border-gray-200">
            <div className="flex-1">
              <label className="block text-xs mb-1">Ingredient</label>
              <Input value={item.ingredient_name} onChange={(e) => {
                const newI = [...items]; newI[i].ingredient_name = e.target.value; setItems(newI);
              }} />
            </div>
            <div className="w-24">
              <label className="block text-xs mb-1">Qty</label>
              <Input type="number" step="0.01" value={item.quantity} onChange={(e) => {
                const newI = [...items]; newI[i].quantity = e.target.value; setItems(newI);
              }} />
            </div>
            <div className="w-24">
              <label className="block text-xs mb-1">Unit</label>
              <select className="w-full h-10 px-3 border border-gray-300 text-sm" value={item.unit} onChange={(e) => {
                const newI = [...items]; newI[i].unit = e.target.value; setItems(newI);
              }}>
                <option value="unit">unit</option>
                <option value="g">g</option>
                <option value="ml">ml</option>
              </select>
            </div>
            <button onClick={() => setItems(items.filter((_, idx) => idx !== i))} className="text-red-500 text-sm pb-2 hover:underline">
              Remove
            </button>
          </div>
        ))}
        <Button variant="secondary" onClick={addItem} className="w-full text-sm py-2">
          + Add Ingredient
        </Button>
        <div className="flex justify-end pt-4">
          <Button onClick={() => saveMutation.mutate(items)} disabled={saveMutation.isPending}>
            Save Recipe
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}

function ModifierManager() {
  const { data: modifierGroups = [] } = useQuery<ModifierGroup[]>({
    queryKey: ['modifier-groups'],
    queryFn: async () => (await api.get('/modifier-groups')).data.data,
  });

  return (
    <Card padding="md">
      <CardContent className="space-y-4">
        <p className="text-sm text-gray-500 mb-4">
          Select which modifier groups apply to this product. (Manage groups in the Modifiers tab).
        </p>
        <div className="space-y-2">
          {modifierGroups.length === 0 ? (
            <p className="text-sm text-gray-400">No modifier groups created yet.</p>
          ) : (
            modifierGroups.map((group) => (
              <label key={group.id} className="flex items-center gap-3 p-3 bg-gray-50 border border-gray-200">
                <input type="checkbox" className="w-4 h-4 text-amber" />
                <div>
                  <p className="font-medium text-charcoal">{group.name}</p>
                  <p className="text-xs text-gray-500">
                    {group.modifiers?.map((m) => m.name).join(', ') || 'No options'}
                  </p>
                </div>
              </label>
            ))
          )}
        </div>
      </CardContent>
    </Card>
  );
}
