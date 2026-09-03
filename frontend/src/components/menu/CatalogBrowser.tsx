'use client';

import { useEffect, useRef, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';

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
  description: string | null;
  products_count: number;
  products: { name: string }[];
}

interface Props {
  existingNames: string[];
}

export default function CatalogBrowser({ existingNames }: Props) {
  const queryClient = useQueryClient();
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<CatalogItem[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  const [searchError, setSearchError] = useState<string | null>(null);
  const [addFeedback, setAddFeedback] = useState<string | null>(null);
  const abortRef = useRef<AbortController | null>(null);

  const existing = new Set(existingNames.map((n) => n.toLowerCase()));

  const { data: packs = [], isLoading: isLoadingPacks } = useQuery<CatalogPack[]>({
    queryKey: ['catalog-packs'],
    queryFn: async () => {
      const res = await api.get('/catalog/packs');
      return res.data.data;
    },
    staleTime: 5 * 60 * 1000,
  });

  useEffect(() => {
    abortRef.current?.abort();
    const controller = new AbortController();
    abortRef.current = controller;

    const timer = setTimeout(async () => {
      if (!query.trim()) {
        if (!controller.signal.aborted) {
          setResults([]);
          setIsSearching(false);
        }
        return;
      }

      setIsSearching(true);
      setSearchError(null);

      try {
        const res = await api.get('/catalog/search', {
          params: { q: query.trim() },
          signal: controller.signal,
        });
        if (!controller.signal.aborted) {
          setResults(res.data.data ?? []);
          setIsSearching(false);
        }
      } catch {
        if (!controller.signal.aborted) {
          setResults([]);
          setSearchError('Could not search the catalog. Please try again.');
          setIsSearching(false);
        }
      }
    }, 250);

    return () => {
      controller.abort();
      clearTimeout(timer);
    };
  }, [query]);

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['products'] });
    queryClient.invalidateQueries({ queryKey: ['categories'] });
  };

  const addItemMutation = useMutation({
    mutationFn: async (itemId: number) => {
      return (await api.post(`/catalog/items/${itemId}/add`)).data.data;
    },
    onSuccess: () => {
      setAddFeedback('Added to your menu — tweak prices anytime.');
      invalidate();
    },
  });

  const applyPackMutation = useMutation({
    mutationFn: async (packId: number) => {
      return (await api.post(`/catalog/packs/${packId}/apply`)).data.data;
    },
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

  return (
    <div className="space-y-6">
      <div>
        <h3 className="text-base font-semibold text-charcoal">Starter packs</h3>
        <p className="text-sm text-gray-500">Pre-filled with Nigerian favourites and suggested prices — edit anytime.</p>
      </div>

      {isLoadingPacks ? (
        <div className="p-4 text-center text-sm text-gray-500">Loading catalog...</div>
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
                    <p className="text-xs text-gray-400">{pack.products_count} products</p>
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

      <div className="border-t border-gray-100 pt-6">
        <h3 className="text-base font-semibold text-charcoal">Browse the catalog</h3>
        <p className="text-sm text-gray-500 mb-4">Search for a drink or spirit and add it — sizes and suggested prices come pre-filled.</p>

        <Input
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Search — e.g. stout, gin, Heineken, Orijin…"
          className="max-w-md"
        />

        {searchError && <p className="mt-2 text-sm text-red-600">{searchError}</p>}

        {isSearching && <p className="mt-3 text-sm text-gray-500">Searching…</p>}

        {!isSearching && query.trim() && results.length === 0 && !searchError && (
          <p className="mt-3 text-sm text-gray-500">No matches. Try &quot;stout&quot;, &quot;gin&quot; or &quot;radler&quot;.</p>
        )}

        {!isSearching && results.length > 0 && (
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
        )}
      </div>

      {addFeedback && (
        <p className="text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">{addFeedback}</p>
      )}
    </div>
  );
}