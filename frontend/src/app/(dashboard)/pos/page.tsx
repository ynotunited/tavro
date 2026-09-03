'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import Logo from '@/components/Logo';

interface Table {
  id: number;
  name: string;
  capacity: number;
  status: 'AVAILABLE' | 'OCCUPIED' | 'CLEANING' | 'RESERVED';
  floor_id: number;
}

interface Floor {
  id: number;
  name: string;
  tables: Table[];
}

const STATUS_STYLE: Record<string, string> = {
  AVAILABLE: 'border-emerald-500 text-emerald-400',
  OCCUPIED:  'border-amber-400  text-amber-300  bg-amber-400/10',
  CLEANING:  'border-sky-500    text-sky-400',
  RESERVED:  'border-purple-500 text-purple-400',
};

export default function POSPage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const [activeFloorId, setActiveFloorId] = useState<number | null>(null);

  const { data: floors = [], isLoading } = useQuery<Floor[]>({
    queryKey: ['floors'],
    queryFn: async () => (await api.get('/floors')).data.data,
  });

  const effectiveFloorId = activeFloorId ?? floors[0]?.id ?? null;

  const openOrderMutation = useMutation({
    mutationFn: async (tableId: number) =>
      (await api.post('/orders', { table_id: tableId })).data.data,
    onSuccess: (order) => {
      queryClient.invalidateQueries({ queryKey: ['floors'] });
      router.push(`/pos/${order.table_id}?orderId=${order.id}`);
    },
  });

  const activeFloor = floors.find(f => f.id === effectiveFloorId);

  const handleTablePress = (table: Table) => {
    if (table.status === 'OCCUPIED') {
      // Existing table — find its active order
      router.push(`/pos/${table.id}`);
    } else if (table.status === 'AVAILABLE') {
      openOrderMutation.mutate(table.id);
    }
  };

  return (
    <div className="min-h-screen bg-[#0F172A] text-white flex flex-col">
      {/* Header */}
      <div className="px-4 pt-safe pt-4 pb-3 border-b border-white/10 flex items-center justify-between">
        <div>
          <h1 className="flex items-center leading-none text-white">
            <Logo variant="white" width={120} />
            <span className="text-white/40 font-normal text-sm ml-2">POS</span>
          </h1>
        </div>
        <button
          onClick={() => router.push('/dashboard')}
          className="text-white/40 text-xs hover:text-white transition-colors"
        >
          ← Back
        </button>
      </div>

      {/* Floor Tabs */}
      <div className="flex overflow-x-auto border-b border-white/10 shrink-0 scrollbar-none">
        {floors.map(floor => (
          <button
            key={floor.id}
            onClick={() => setActiveFloorId(floor.id)}
            className={`px-5 py-3 text-sm font-medium whitespace-nowrap transition-colors ${
              effectiveFloorId === floor.id
                ? 'text-amber-400 border-b-2 border-amber-400'
                : 'text-white/50 hover:text-white'
            }`}
          >
            {floor.name}
          </button>
        ))}
      </div>

      {/* Status Legend */}
      <div className="flex gap-4 px-4 py-2 text-xs text-white/40 border-b border-white/5">
        <span className="flex items-center gap-1.5"><span className="w-2 h-2 rounded-full bg-emerald-500"></span>Available</span>
        <span className="flex items-center gap-1.5"><span className="w-2 h-2 rounded-full bg-amber-400"></span>Occupied</span>
        <span className="flex items-center gap-1.5"><span className="w-2 h-2 rounded-full bg-sky-500"></span>Cleaning</span>
        <span className="flex items-center gap-1.5"><span className="w-2 h-2 rounded-full bg-purple-500"></span>Reserved</span>
      </div>

      {/* Table Grid */}
      <div className="flex-1 overflow-y-auto p-4">
        {isLoading ? (
          <div className="grid grid-cols-3 md:grid-cols-5 gap-3">
            {Array.from({ length: 9 }).map((_, i) => (
              <div key={i} className="h-24 bg-white/5 animate-pulse rounded-lg" />
            ))}
          </div>
        ) : activeFloor?.tables.length === 0 ? (
          <div className="flex flex-col items-center justify-center h-48 text-white/30">
            <p className="text-4xl mb-2">🪑</p>
            <p>No tables on this floor</p>
          </div>
        ) : (
          <div className="grid grid-cols-3 md:grid-cols-5 gap-3">
            {activeFloor?.tables.map(table => (
              <button
                key={table.id}
                onClick={() => handleTablePress(table)}
                disabled={openOrderMutation.isPending || ['CLEANING', 'RESERVED'].includes(table.status)}
                className={`relative h-24 border-2 rounded-lg flex flex-col items-center justify-center gap-1 transition-all active:scale-95
                  ${STATUS_STYLE[table.status]}
                  ${['CLEANING', 'RESERVED'].includes(table.status) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/5 cursor-pointer'}
                `}
              >
                <span className="font-bold text-lg leading-none">{table.name}</span>
                <span className="text-xs opacity-60">{table.capacity} seats</span>
                {table.status === 'OCCUPIED' && (
                  <span className="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-amber-400 animate-pulse" />
                )}
              </button>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
