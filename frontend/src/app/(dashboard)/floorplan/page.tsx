'use client';

import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { useAuthStore } from '@/store/authStore';
import { sanitizeString } from '@/lib/sanitize';
import { echo } from '@/lib/echo';
import { Button } from '@/components/ui/Button';
import { Card, CardContent } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
import { Modal } from '@/components/ui/Modal';
import { DndContext, useDraggable, useSensor, useSensors, PointerSensor, DragEndEvent } from '@dnd-kit/core';
import { CSS } from '@dnd-kit/utilities';

interface Floor {
  id: number;
  name: string;
  tables: Table[];
}

interface Table {
  id: number;
  name: string;
  capacity: number;
  status: 'AVAILABLE' | 'OCCUPIED' | 'CLEANING' | 'RESERVED';
  pos_x: number;
  pos_y: number;
  shape: string;
}

const statusColors = {
  AVAILABLE: 'bg-green-100 text-green-700 border-green-300',
  OCCUPIED: 'bg-amber-100 text-amber-700 border-amber-300',
  CLEANING: 'bg-blue-100 text-blue-700 border-blue-300',
  RESERVED: 'bg-purple-100 text-purple-700 border-purple-300',
};

// ─── Draggable Table Component ──────────────────────────────────────────────

function DraggableTable({ table, onClick }: { table: Table; onClick: () => void }) {
  const { attributes, listeners, setNodeRef, transform } = useDraggable({
    id: table.id.toString(),
    data: { table }
  });

  const style = transform ? {
    transform: CSS.Translate.toString(transform),
  } : undefined;

  return (
    <div
      ref={setNodeRef}
      style={{
        ...style,
        position: 'absolute',
        left: `${table.pos_x}px`,
        top: `${table.pos_y}px`,
      }}
      className={`absolute w-24 h-24 ${statusColors[table.status]} border-2 rounded-xl flex flex-col justify-center items-center shadow-sm cursor-grab active:cursor-grabbing hover:shadow-md transition-shadow`}
      {...listeners}
      {...attributes}
      onClick={(e) => {
        // Prevent click if we just dragged
        if (!transform) onClick();
      }}
    >
      <span className="font-bold text-lg">{table.name}</span>
      <span className="text-xs opacity-80">Seats {table.capacity}</span>
    </div>
  );
}

// ─── Main Page ────────────────────────────────────────────────────────────

export default function FloorPlanPage() {
  const queryClient = useQueryClient();
  const { user } = useAuthStore();
  const [activeFloorId, setActiveFloorId] = useState<number | null>(null);
  const [isFloorModalOpen, setIsFloorModalOpen] = useState(false);
  const [isTableModalOpen, setIsTableModalOpen] = useState(false);
  const [selectedTable, setSelectedTable] = useState<Table | null>(null);

  // Fetch Floors & Tables
  const { data: floors = [], isLoading } = useQuery<Floor[]>({
    queryKey: ['floors'],
    queryFn: async () => (await api.get('/floors')).data.data,
  });

  const effectiveFloorId = activeFloorId ?? floors[0]?.id ?? null;
  const activeFloor = floors.find(f => f.id === effectiveFloorId);

  // WebSocket Subscription
  useEffect(() => {
    if (!user?.branch_id || !echo) return;

    const channelName = `private-branch.${user.branch_id}.tables`;
    console.log('Subscribing to:', channelName);

    const channel = echo.private(channelName);
    
    channel.listen('TableStatusUpdated', (e: { id: number } & Partial<Table>) => {
      console.log('Table Updated via WS:', e);
      queryClient.setQueryData(['floors'], (old: Floor[] | undefined) => {
        if (!old) return old;
        return old.map(floor => ({
          ...floor,
          tables: floor.tables.map(table => 
            table.id === e.id ? { ...table, ...e } : table
          )
        }));
      });
    });

    return () => {
      echo?.leave(channelName);
    };
  }, [user?.branch_id, queryClient]);

  // Mutations
  const createFloorMutation = useMutation({
    mutationFn: async (name: string) => api.post('/floors', { name: sanitizeString(name) }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['floors'] });
      setIsFloorModalOpen(false);
    }
  });

  const createTableMutation = useMutation({
    mutationFn: async (data: { name: string; capacity: number; floor_id: number | null; pos_x: number; pos_y: number; shape: string }) => api.post('/tables', data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['floors'] });
      setIsTableModalOpen(false);
    }
  });

  const updateTableMutation = useMutation({
    mutationFn: async (data: { id: number, payload: Partial<Table> }) => api.patch(`/tables/${data.id}`, data.payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['floors'] });
    }
  });

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, delta } = event;
    if (delta.x === 0 && delta.y === 0) return; // Didn't move

    const table = active.data.current?.table as Table;
    if (!table) return;

    // Optimistically update
    const newX = Math.max(0, table.pos_x + delta.x);
    const newY = Math.max(0, table.pos_y + delta.y);

    queryClient.setQueryData(['floors'], (old: Floor[] | undefined) => {
      if (!old) return old;
      return old.map(floor => ({
        ...floor,
        tables: floor.tables.map(t => 
          t.id === table.id ? { ...t, pos_x: newX, pos_y: newY } : t
        )
      }));
    });

    updateTableMutation.mutate({
      id: table.id,
      payload: { pos_x: newX, pos_y: newY }
    });
  };

  const handleStatusChange = (status: Table['status']) => {
    if (!selectedTable) return;
    updateTableMutation.mutate({
      id: selectedTable.id,
      payload: { status }
    });
    setSelectedTable(null);
  };

  const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 5 } }));

  if (isLoading) return <div className="p-8 text-center">Loading floor plan...</div>;

  return (
    <div className="space-y-6 h-[calc(100vh-8rem)] flex flex-col">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-charcoal">Floor Plan</h1>
          <p className="text-sm text-gray-500">Manage tables and live seating status.</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" size="sm" onClick={() => setIsFloorModalOpen(true)}>+ Add Floor</Button>
          <Button size="sm" onClick={() => setIsTableModalOpen(true)} disabled={!effectiveFloorId}>+ Add Table</Button>
        </div>
      </div>

      {floors.length === 0 ? (
        <Card className="flex-1 flex items-center justify-center">
          <p className="text-gray-500">No floors created yet. Add a floor to begin.</p>
        </Card>
      ) : (
        <div className="flex flex-col flex-1 overflow-hidden border border-gray-200 bg-gray-50 rounded-lg">
          
          {/* Floor Tabs */}
          <div className="flex bg-white border-b border-gray-200 overflow-x-auto">
            {floors.map(floor => (
              <button
                key={floor.id}
                onClick={() => setActiveFloorId(floor.id)}
                className={`px-6 py-3 text-sm font-medium whitespace-nowrap transition-colors ${
                  activeFloorId === floor.id ? 'border-b-2 border-amber text-charcoal bg-amber-50/30' : 'text-gray-500 hover:text-charcoal'
                }`}
              >
                {floor.name}
              </button>
            ))}
          </div>

          {/* Interactive Map Area (Desktop) */}
          <div className="flex-1 relative overflow-auto hidden md:block">
            <DndContext sensors={sensors} onDragEnd={handleDragEnd}>
              <div className="min-w-[1200px] min-h-[800px] relative p-8">
                {activeFloor?.tables.map(table => (
                  <DraggableTable 
                    key={table.id} 
                    table={table} 
                    onClick={() => setSelectedTable(table)}
                  />
                ))}
              </div>
            </DndContext>
          </div>

          {/* Mobile List View */}
          <div className="flex-1 overflow-y-auto md:hidden p-4 space-y-3">
            {activeFloor?.tables.length === 0 && (
              <p className="text-center text-gray-500 py-8">No tables on this floor.</p>
            )}
            {activeFloor?.tables.map(table => (
              <div 
                key={table.id} 
                onClick={() => setSelectedTable(table)}
                className={`flex justify-between items-center p-4 rounded-lg border ${statusColors[table.status]} shadow-sm`}
              >
                <div>
                  <h3 className="font-bold text-lg">{table.name}</h3>
                  <p className="text-sm opacity-80">Capacity: {table.capacity}</p>
                </div>
                <div className="text-right">
                  <span className="text-xs font-bold tracking-wider opacity-80 block mb-1">STATUS</span>
                  <span className="capitalize font-medium">{table.status.toLowerCase()}</span>
                </div>
              </div>
            ))}
          </div>

        </div>
      )}

      {/* Modals */}
      <Modal isOpen={isFloorModalOpen} onClose={() => setIsFloorModalOpen(false)} title="Add New Floor">
        <form onSubmit={(e: React.FormEvent<HTMLFormElement>) => { e.preventDefault(); createFloorMutation.mutate((e.currentTarget.elements.namedItem('name') as HTMLInputElement).value); }} className="space-y-4">
          <Input name="name" placeholder="e.g. Main Dining Room" required maxLength={255} />
          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="secondary" onClick={() => setIsFloorModalOpen(false)}>Cancel</Button>
            <Button type="submit">Save</Button>
          </div>
        </form>
      </Modal>

      <Modal isOpen={isTableModalOpen} onClose={() => setIsTableModalOpen(false)} title="Add New Table">
        <form onSubmit={(e: React.FormEvent<HTMLFormElement>) => { 
          e.preventDefault(); 
          createTableMutation.mutate({
            name: sanitizeString((e.currentTarget.elements.namedItem('name') as HTMLInputElement).value),
            capacity: Number((e.currentTarget.elements.namedItem('capacity') as HTMLInputElement).value),
            floor_id: effectiveFloorId,
            pos_x: 50,
            pos_y: 50,
            shape: 'square',
          }); 
        }} className="space-y-4">
          <div>
            <label className="block text-sm mb-1">Table Name/Number</label>
            <Input name="name" placeholder="e.g. T-1" required maxLength={255} />
          </div>
          <div>
            <label className="block text-sm mb-1">Capacity</label>
            <Input name="capacity" type="number" defaultValue="2" required />
          </div>
          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="secondary" onClick={() => setIsTableModalOpen(false)}>Cancel</Button>
            <Button type="submit">Save</Button>
          </div>
        </form>
      </Modal>

      {/* Table Actions Modal / Sheet */}
      <Modal isOpen={!!selectedTable} onClose={() => setSelectedTable(null)} title={selectedTable?.name}>
        <div className="space-y-6">
          <div className="grid grid-cols-2 gap-4">
            <Button variant="secondary" onClick={() => handleStatusChange('AVAILABLE')}>Mark Available</Button>
            <Button variant="primary" onClick={() => handleStatusChange('OCCUPIED')}>Mark Occupied</Button>
            <Button variant="secondary" onClick={() => handleStatusChange('RESERVED')}>Mark Reserved</Button>
            <Button variant="secondary" onClick={() => handleStatusChange('CLEANING')}>Mark Cleaning</Button>
          </div>
          
          <div className="pt-4 border-t border-gray-100 space-y-2">
            <p className="text-sm font-medium">Quick Actions</p>
            <Button variant="secondary" className="w-full justify-start text-left">📝 Take Order</Button>
            <Button variant="secondary" className="w-full justify-start text-left">🧾 View Bill</Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
