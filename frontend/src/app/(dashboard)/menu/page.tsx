'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { trimStrings } from '@/lib/sanitize';
import { Button } from '@/components/ui/Button';
import { Card, CardContent } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
import { Modal } from '@/components/ui/Modal';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/Table';
import { DraggableCategoryList } from '@/components/menu/DraggableCategoryList';
import CatalogBrowser from '@/components/menu/CatalogBrowser';

interface Category {
  id: number;
  name: string;
  color: string;
  sort_order: number;
  products_count: number;
}

interface Product {
  id: number;
  name: string;
  category_id: number;
  selling_price: string;
  is_available: boolean;
  image_path: string | null;
  category?: Category;
}

export default function MenuPage() {
  const queryClient = useQueryClient();
  const router = useRouter();
  const [activeTab, setActiveTab] = useState<'products' | 'categories'>('products');
  
  // Category State
  const [isCategoryModalOpen, setIsCategoryModalOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState<Category | null>(null);

  // Data Fetching
  const { data: categories = [], isLoading: isLoadingCategories } = useQuery<Category[]>({
    queryKey: ['categories'],
    queryFn: async () => {
      const res = await api.get('/categories');
      return res.data.data;
    }
  });

  const { data: products = [], isLoading: isLoadingProducts } = useQuery<Product[]>({
    queryKey: ['products'],
    queryFn: async () => {
      const res = await api.get('/products');
      return res.data.data;
    }
  });

  // Category Mutation
  const saveCategoryMutation = useMutation({
    mutationFn: async (data: Partial<Category>) => {
      if (data.id) return api.patch(`/categories/${data.id}`, data);
      return api.post('/categories', data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['categories'] });
      setIsCategoryModalOpen(false);
      setEditingCategory(null);
    }
  });



  const toggleAvailabilityMutation = useMutation({
    mutationFn: async (productId: number) => {
      return api.patch(`/products/${productId}/availability`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['products'] });
    }
  });

  const handleCategorySubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    saveCategoryMutation.mutate(trimStrings({
      id: editingCategory?.id,
      name: formData.get('name') as string,
      color: formData.get('color') as string,
    }));
  };



  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-charcoal">Menu Management</h1>
          <p className="text-sm text-gray-500">Manage your products and categories.</p>
        </div>
        <div className="flex gap-2">
          <Button variant={activeTab === 'categories' ? 'primary' : 'secondary'} onClick={() => setActiveTab('categories')}>Categories</Button>
          <Button variant={activeTab === 'products' ? 'primary' : 'secondary'} onClick={() => setActiveTab('products')}>Products</Button>
        </div>
      </div>

      {activeTab === 'categories' && (
        <Card>
          <div className="p-4 border-b border-gray-100 flex justify-between items-center">
            <h2 className="font-semibold text-charcoal">Categories</h2>
            <Button size="sm" onClick={() => { setEditingCategory(null); setIsCategoryModalOpen(true); }}>+ Add Category</Button>
          </div>
          <CardContent className="p-0">
            {isLoadingCategories ? (
              <div className="p-6 text-center text-sm text-gray-500">Loading categories...</div>
            ) : categories.length === 0 ? (
              <div className="p-8 text-center text-gray-500">No categories found.</div>
            ) : (
              <DraggableCategoryList 
                initialCategories={categories} 
                onEdit={(cat) => { setEditingCategory(cat); setIsCategoryModalOpen(true); }} 
              />
            )}
          </CardContent>
        </Card>
      )}

      {activeTab === 'products' && (
        <>
          <CatalogBrowser existingNames={products.map((p) => p.name)} />
          <Card>
          <div className="p-4 border-b border-gray-100 flex justify-between items-center">
            <h2 className="font-semibold text-charcoal">Products</h2>
            <Button size="sm" onClick={() => router.push('/menu/products/new')}>+ Add Product</Button>
          </div>
          <CardContent className="p-0">
            {isLoadingProducts ? (
              <div className="p-6 text-center text-sm text-gray-500">Loading products...</div>
            ) : products.length === 0 ? (
              <div className="p-8 text-center text-gray-500">No products found.</div>
            ) : (
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Product</TableHead>
                      <TableHead>Category</TableHead>
                      <TableHead>Price</TableHead>
                      <TableHead>Availability</TableHead>
                      <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {products.map((product) => (
                      <TableRow key={product.id}>
                        <TableCell>
                          <p className="font-medium text-charcoal">{product.name}</p>
                        </TableCell>
                        <TableCell className="text-gray-500">{product.category?.name || '—'}</TableCell>
                        <TableCell className="text-gray-500">₦{Number(product.selling_price).toLocaleString()}</TableCell>
                        <TableCell>
                          <button 
                            onClick={() => toggleAvailabilityMutation.mutate(product.id)}
                            className={`px-2 py-1 text-xs rounded-full ${product.is_available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}
                          >
                            {product.is_available ? 'Available' : 'Unavailable'}
                          </button>
                        </TableCell>
                        <TableCell className="text-right">
                           <button onClick={() => router.push(`/menu/products/${product.id}`)} className="text-amber hover:text-amber/80 text-sm font-medium">
                            Edit
                          </button>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            )}
          </CardContent>
          </Card>
        </>
      )}

      {/* Category Modal */}
      <Modal isOpen={isCategoryModalOpen} onClose={() => setIsCategoryModalOpen(false)} title={editingCategory ? 'Edit Category' : 'Add Category'}>
        <form onSubmit={handleCategorySubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-1">Name *</label>
            <CategoryNameInput key={editingCategory?.id ?? 'new'} defaultValue={editingCategory?.name ?? ''} />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">Color Code</label>
            <Input name="color" type="color" defaultValue={editingCategory?.color || '#F59E0B'} className="h-10" />
          </div>
          <div className="pt-4 flex justify-end gap-2">
            <Button type="button" variant="secondary" onClick={() => setIsCategoryModalOpen(false)}>Cancel</Button>
            <Button type="submit" disabled={saveCategoryMutation.isPending}>Save</Button>
          </div>
        </form>
      </Modal>

    </div>
  );
}

function CategoryNameInput({ defaultValue }: { defaultValue: string }) {
  const [value, setValue] = useState(defaultValue);

  const { data: catalogCategories = [] } = useQuery<{ id: number; name: string }[]>({
    queryKey: ['catalog-categories'],
    queryFn: async () => {
      const res = await api.get('/catalog/categories');
      return res.data.data;
    },
    staleTime: 5 * 60 * 1000,
  });

  const term = value.trim().toLowerCase();
  const suggestions = term
    ? catalogCategories.filter((c) => c.name.toLowerCase().includes(term)).slice(0, 4)
    : [];

  return (
    <div className="space-y-2">
      <Input name="name" required value={value} onChange={(e) => setValue(e.target.value)} />
      {suggestions.length > 0 && (
        <div className="flex flex-wrap gap-1.5">
          {suggestions.map((c) => (
            <button
              key={c.id}
              type="button"
              onClick={() => setValue(c.name)}
              className="text-xs border border-gray-200 rounded-full px-2.5 py-1 text-gray-600 hover:border-amber hover:text-amber transition-colors"
            >
              {c.name}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
