'use client';

import { useState, useEffect } from 'react';
import {
  DndContext,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
  DragEndEvent,
} from '@dnd-kit/core';
import {
  arrayMove,
  SortableContext,
  sortableKeyboardCoordinates,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';

interface Category {
  id: number;
  name: string;
  color: string;
  sort_order: number;
  products_count: number;
}

function SortableItem({ category, onEdit }: { category: Category; onEdit: (c: Category) => void }) {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
  } = useSortable({ id: category.id });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };

  return (
    <div
      ref={setNodeRef}
      style={style}
      className="p-4 flex justify-between items-center bg-white border-b border-gray-100 hover:bg-gray-50 transition-colors"
    >
      <div className="flex items-center gap-3">
        <div
          {...attributes}
          {...listeners}
          className="cursor-grab p-2 -ml-2 text-gray-400 hover:text-gray-600"
        >
          {/* Drag Handle Icon (6 dots) */}
          <svg width="14" height="20" viewBox="0 0 14 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <circle cx="4" cy="4" r="2" />
            <circle cx="10" cy="4" r="2" />
            <circle cx="4" cy="10" r="2" />
            <circle cx="10" cy="10" r="2" />
            <circle cx="4" cy="16" r="2" />
            <circle cx="10" cy="16" r="2" />
          </svg>
        </div>
        <div className="w-4 h-4 rounded-full" style={{ backgroundColor: category.color || '#ccc' }}></div>
        <div>
          <p className="font-medium text-charcoal">{category.name}</p>
          <p className="text-xs text-gray-400">{category.products_count} products</p>
        </div>
      </div>
      <button onClick={() => onEdit(category)} className="text-amber text-sm font-medium">
        Edit
      </button>
    </div>
  );
}

export function DraggableCategoryList({ initialCategories, onEdit }: { initialCategories: Category[]; onEdit: (c: Category) => void }) {
  const [items, setItems] = useState(initialCategories);
  const queryClient = useQueryClient();

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    setItems(initialCategories);
  }, [initialCategories]);

  const reorderMutation = useMutation({
    mutationFn: async (ids: number[]) => api.post('/categories/reorder', { ids }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['categories'] });
    }
  });

  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates })
  );

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;
    if (active.id !== over?.id) {
      setItems((items) => {
        const oldIndex = items.findIndex((i) => i.id === active.id);
        const newIndex = items.findIndex((i) => i.id === over?.id);
        const newItems = arrayMove(items, oldIndex, newIndex);
        
        // Save to backend
        reorderMutation.mutate(newItems.map(i => i.id));
        
        return newItems;
      });
    }
  };

  return (
    <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
      <SortableContext items={items.map((i) => i.id)} strategy={verticalListSortingStrategy}>
        <div className="flex flex-col">
          {items.map((category) => (
            <SortableItem key={category.id} category={category} onEdit={onEdit} />
          ))}
        </div>
      </SortableContext>
    </DndContext>
  );
}
