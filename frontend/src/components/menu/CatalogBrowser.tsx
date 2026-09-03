'use client';

import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';

type Tab = 'drink' | 'food';

interface CatalogVariant {
  id: number;
  name: string;
  size_label: string | null;
  suggested_selling_price: string;
}

interface CatalogItem {
  id: number;
  name: string;
  brand: string | null;
  type: string;
  is_alcoholic: boolean;
  description: string | null;
  category: { id: number; name: string } | null;
  variants: CatalogVariant[];
}

interface CatalogPack {
  id: number;
  name: string;
  slug: string;
  type: string;
  description: string | null;
  products_count: number;
  products: { name: string }[];
}

interface PageMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

interface PageResult {
  items: CatalogItem[];
  meta: PageMeta;
}

interface Props {
  existingNames: string[];
}

const PER_PAGE = 12;

export default function CatalogBrowser({ existingNames }: Props) {
  const queryClient = useQueryClient();
  const [tab, setTab] = useState<Tab>('drink');
  const [query, setQuery] = useState('');
  const [debouncedQuery, setDebouncedQuery] = useState('');
  const [page, setPage] = useState(1);
  const [addFeedback, setAddFeedback] = useState<string | null>(null);

  const existing = new Set(existingNames.map((n) => n.toLowerCase()));

  useEffect(() => {
    const timer = setTimeout(() => setDebouncedQuery(query.trim()), 300);
    return () => clearTimeout(timer);
  }, [query]);

  const changeTab = (next: Tab) => {
    setTab(next);
    setQuery('');
    setDebouncedQuery('');
    setPage(1);
  };

  const { data: packs = [], isLoading: isLoadingPacks } = useQuery<CatalogPack[]>({
    queryKey: ['catalog-packs', tab],
    queryFn: async () => (await api.get('/catalog/packs', { params: { type: tab } })).data.data,
    staleTime: 5 * 60 * 1000,
  });

  const { data: pageResult, isFetching: isSearching } = useQuery<PageResult>({
    queryKey: ['catalog-search', tab, debouncedQuery, page],
    queryFn: async () => {
      const res = await api.get('/catalog/search', {
        params: { type: tab, q: debouncedQuery, page, per_page: PER_PAGE },
      });
      return { items: res.data.data ?? [], meta: res.data.meta };
    },
    staleTime: 30 * 1000,
  });

  const results = pageResult?.items ?? [];
  const meta = pageResult?.meta;

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['products'] });
    queryClient.invalidateQueries({ queryKey: ['categories'] });
  };

  const addItemMutation = useMutation({
    mutationFn: async (itemId: number) => (await api.post(`/catalog/items/${itemId}/add`)).data.data,
    onSuccess: () => {
      setAddFeedback('Added to your menu — tweak prices anytime.');
      invalidate();
    },
  });

  const applyPackMutation = useMutation({
    mutationFn: async (packId: number) => (await api.post(`/catalog/packs/${packId}/apply`)).data.data,
    onSuccess: (data: { added: number }) => {
      setAddFeedback(
        data.added > 0
          ? `Added ${data.added} products to your menu.`
          : 'Everything in that pack is already on your menu.'
      );
      invalidate();
    },
  });

  const formatPrice = (value: string) => `₦${Number(value).toLocaleString()}`;
  const isAdded = (name: string) => existing.has(name.toLowerCase());

  const showResults = debouncedQuery !== '' || (meta?.total ?? 0) > 0;
  const canPrev = (meta?.current_page ?? 1) > 1;
  const canNext = (meta?.current_page ?? 1) < (meta?.last_page ?? 1);

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3">
        <h3 className="text-base font-semibold text-charcoal">Add from the catalog</h3>
        <div className="inline-flex rounded-lg border border-gray-200 p-1 w-fit">
          {(['drink', 'food'] as Tab[]).map((t) => (
            <button
              key={t}
              onClick={() => changeTab(t)}
              className={`px-4 py-1.5 text-sm font-medium rounded-md transition-colors ${
                tab === t ? 'bg-charcoal text-white' : 'text-gray-600 hover:text-charcoal'
              }`}
            >
              {t === 'drink' ? 'Drinks' : 'Food'}
            </button>
          ))}
        </div>
        <p className="text-sm text-gray-500">
          {tab === 'drink'
            ? 'Alcohol, soft drinks, juices and more — build a full bar in one tap, then tweak prices.'
            : 'Nigerian favourites — foods, stews and soups ship with no price; set your own after adding.'}
        </p>
      </div>

      <div>
        <h3 className="text-base font-semibold text-charcoal">Starter packs</h3>
        <p className="text-sm text-gray-500 mb-3">
          {tab === 'drink' ? 'Pre-filled with a complete bar — edit anytime.' : 'Pre-filled with everyday Nigerian dishes.'}
        </p>

        {isLoadingPacks ? (
          <div className="p-4 text-center text-sm text-gray-500">Loading catalog...</div>
        ) : packs.length === 0 ? (
          <div className="p-4 text-center text-sm text-gray-500">No packs available for this section.</div>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {packs.map((pack) => {
              const allPresent = pack.products.every((p) => isAdded(p.name));
              const busy = applyPackMutation.isPending && applyPackMutation.variables === pack.id;
              return (
                <div key={pack.id} className="border border-gray-200 rounded-xl p-4 flex flex-col gap-2">
                  <div className="flex items-start justify-between gap-2">
                    <div>
                      <p className="font-semibold text-charcoal">{pack.name}</p>
                      <p className="text-xs text-gray-400">{pack.products_count} items</p>
                    </div>
                    <Button
                      size="sm"
                      variant={allPresent ? 'secondary' : 'primary'}
                      disabled={busy || allPresent}
                      onClick={() => applyPackMutation.mutate(pack.id)}
                    >
                      {busy ? 'Adding…' : allPresent ? 'Added' : 'Add pack'}
                    </Button>
                  </div>
                  {pack.description && <p className="text-sm text-gray-500">{pack.description}</p>}
                  <p className="text-xs text-gray-400 line-clamp-2">
                    {pack.products.map((p) => p.name).join(', ')}
                  </p>
                </div>
              );
            })}
          </div>
        )}
      </div>

      <div className="border-t border-gray-100 pt-6">
        <h3 className="text-base font-semibold text-charcoal">
          {tab === 'drink' ? 'Browse drinks' : 'Browse food'}
        </h3>
        <p className="text-sm text-gray-500 mb-4">
          {tab === 'drink'
            ? 'Search or flip through every drink — sizes and suggested prices come pre-filled.'
            : 'Search or browse every Nigerian dish — add and set your own price.'}
        </p>

        <Input
          value={query}
          onChange={(e) => {
            setQuery(e.target.value);
            setPage(1);
          }}
          placeholder={tab === 'drink' ? 'Search — e.g. stout, gin, Heineken, Orijin…' : 'Search — e.g. jollof, egusi, suya…'}
          className="max-w-md"
        />

        {isSearching && <p className="mt-3 text-sm text-gray-500">Loading…</p>}

        {!isSearching && showResults && results.length === 0 && (
          <p className="mt-3 text-sm text-gray-500">No matches in this section.</p>
        )}

        {!isSearching && results.length > 0 && (
          <>
            <ul className="mt-4 divide-y divide-gray-100 border border-gray-200 rounded-xl overflow-hidden">
              {results.map((item) => {
                const added = isAdded(item.name);
                const busy = addItemMutation.isPending && addItemMutation.variables === item.id;
                return (
                  <li key={item.id} className="p-4 flex items-start justify-between gap-4 bg-white">
                    <div className="min-w-0">
                      <div className="flex items-center gap-2 flex-wrap">
                        <p className="font-medium text-charcoal">{item.name}</p>
                        {item.brand && <span className="text-xs text-gray-400">{item.brand}</span>}
                        {item.category && (
                          <span className="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">{item.category.name}</span>
                        )}
                      </div>
                      {item.variants.length > 0 && (
                        <div className="mt-2 flex flex-wrap gap-1.5">
                          {item.variants.map((v) => (
                            <span key={v.id} className="text-xs border border-gray-200 rounded-full px-2 py-0.5 text-gray-600">
                              {v.name} · {formatPrice(v.suggested_selling_price)}
                            </span>
                          ))}
                        </div>
                      )}
                    </div>
                    <Button
                      size="sm"
                      variant={added ? 'secondary' : 'primary'}
                      disabled={busy || added}
                      onClick={() => addItemMutation.mutate(item.id)}
                    >
                      {busy ? 'Adding…' : added ? 'Added' : 'Add'}
                    </Button>
                  </li>
                );
              })}
            </ul>

            {meta && meta.total > PER_PAGE && (
              <div className="mt-4 flex items-center justify-between">
                <p className="text-xs text-gray-500">
                  Showing {(meta.current_page - 1) * PER_PAGE + 1}–{Math.min(meta.current_page * PER_PAGE, meta.total)} of {meta.total}
                </p>
                <div className="flex gap-2">
                  <Button size="sm" variant="secondary" disabled={!canPrev} onClick={() => setPage((p) => p - 1)}>
                    Previous
                  </Button>
                  <Button size="sm" variant="secondary" disabled={!canNext} onClick={() => setPage((p) => p + 1)}>
                    Next
                  </Button>
                </div>
              </div>
            )}
          </>
        )}
      </div>

      {addFeedback && (
        <p className="text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">{addFeedback}</p>
      )}
    </div>
  );
}
