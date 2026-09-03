'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { trimStrings } from '@/lib/sanitize';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/Table';
import { Modal } from '@/components/ui/Modal';
import { Input } from '@/components/ui/Input';

interface Branch {
  id: number;
  name: string;
  address: string | null;
  phone: string | null;
  timezone: string;
}

export default function BranchManagementPage() {
  const queryClient = useQueryClient();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editing, setEditing] = useState<Branch | null>(null);

  const { data: branches, isLoading } = useQuery<Branch[]>({
    queryKey: ['branches'],
    queryFn: async () => {
      const res = await api.get('/branches');
      return res.data.data;
    },
  });

  const upsertMutation = useMutation({
    mutationFn: async (data: Partial<Branch> & { id?: number }) => {
      if (data.id) {
        return api.patch(`/branches/${data.id}`, data);
      }
      return api.post('/branches', data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['branches'] });
      setIsModalOpen(false);
      setEditing(null);
    },
  });

  const openCreate = () => {
    setEditing(null);
    setIsModalOpen(true);
  };

  const openEdit = (branch: Branch) => {
    setEditing(branch);
    setIsModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    upsertMutation.mutate(trimStrings({
      id: editing?.id,
      name: formData.get('name') as string,
      address: formData.get('address') as string,
      phone: formData.get('phone') as string,
      timezone: (formData.get('timezone') as string) || 'Africa/Lagos',
    }));
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-charcoal">Branch Management</h1>
          <p className="text-sm text-gray-500">Manage all your business locations.</p>
        </div>
        <Button onClick={openCreate}>+ Add Branch</Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Branches</CardTitle>
        </CardHeader>
        <CardContent className="p-0">
          {isLoading ? (
            <div className="p-6 text-sm text-gray-500">Loading branches...</div>
          ) : !branches?.length ? (
            <div className="p-8 text-center text-gray-500">
              <p className="font-medium">No branches yet</p>
              <p className="text-sm mt-1">Add your first branch to get started.</p>
              <Button className="mt-4" onClick={openCreate}>Add Branch</Button>
            </div>
          ) : (
            <>
              {/* Mobile card list */}
              <div className="md:hidden divide-y divide-gray-100">
                {branches.map((branch) => (
                  <div key={branch.id} className="p-4 flex justify-between items-start">
                    <div>
                      <p className="font-medium text-charcoal">{branch.name}</p>
                      {branch.address && <p className="text-xs text-gray-500 mt-0.5">{branch.address}</p>}
                      {branch.phone && <p className="text-xs text-gray-500">{branch.phone}</p>}
                    </div>
                    <button onClick={() => openEdit(branch)} className="text-amber text-sm font-medium ml-4 shrink-0">
                      Edit
                    </button>
                  </div>
                ))}
              </div>

              {/* Desktop table */}
              <div className="hidden md:block overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Name</TableHead>
                      <TableHead>Address</TableHead>
                      <TableHead>Phone</TableHead>
                      <TableHead>Timezone</TableHead>
                      <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {branches.map((branch) => (
                      <TableRow key={branch.id}>
                        <TableCell className="font-medium">{branch.name}</TableCell>
                        <TableCell className="text-gray-500">{branch.address || '—'}</TableCell>
                        <TableCell className="text-gray-500">{branch.phone || '—'}</TableCell>
                        <TableCell className="text-gray-500">{branch.timezone}</TableCell>
                        <TableCell className="text-right">
                          <button onClick={() => openEdit(branch)} className="text-amber hover:text-amber/80 text-sm font-medium">
                            Edit
                          </button>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            </>
          )}
        </CardContent>
      </Card>

      <Modal
        isOpen={isModalOpen}
        onClose={() => { setIsModalOpen(false); setEditing(null); }}
        title={editing ? 'Edit Branch' : 'Add New Branch'}
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-1">Branch Name *</label>
            <Input name="name" required defaultValue={editing?.name} placeholder="e.g. Victoria Island Branch" />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">Address</label>
            <Input name="address" defaultValue={editing?.address || ''} placeholder="e.g. 12 Adeola Odeku St, Lagos" />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">Phone</label>
            <Input name="phone" defaultValue={editing?.phone || ''} placeholder="e.g. +234 801 234 5678" />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">Timezone</label>
            <select name="timezone" defaultValue={editing?.timezone || 'Africa/Lagos'}
              className="w-full px-3 py-2 border border-gray-300 focus:outline-none focus:ring-1 focus:ring-amber text-sm">
              <option value="Africa/Lagos">Africa/Lagos (WAT)</option>
              <option value="Africa/Abidjan">Africa/Abidjan (GMT)</option>
              <option value="Africa/Nairobi">Africa/Nairobi (EAT)</option>
            </select>
          </div>
          <div className="pt-4 flex justify-end gap-2">
            <Button type="button" variant="secondary" onClick={() => setIsModalOpen(false)}>Cancel</Button>
            <Button type="submit" disabled={upsertMutation.isPending}>
              {upsertMutation.isPending ? 'Saving...' : editing ? 'Save Changes' : 'Create Branch'}
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
