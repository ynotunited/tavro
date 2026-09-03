"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { ChefHat, ArrowLeft } from "lucide-react";

const legalPages = [
  { href: "/privacy", label: "Privacy Policy" },
  { href: "/terms", label: "Terms of Use" },
  { href: "/compliance", label: "Data & Compliance" },
  { href: "/ip-infringement", label: "IP Infringement" },
  { href: "/acceptable-use", label: "Acceptable Use" },
  { href: "/cookies", label: "Cookie Policy" },
];

export default function LegalLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();

  return (
    <div className="min-h-screen flex flex-col font-sans bg-white selection:bg-amber-500/30">
      {/* Top Nav */}
      <nav className="border-b border-charcoal-100 bg-white/80 backdrop-blur-md sticky top-0 z-50">
        <div className="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
          <Link href="/" className="flex items-center gap-2 text-charcoal-500 hover:text-charcoal-900 transition-colors text-sm font-medium">
            <ArrowLeft className="w-4 h-4" />
            Back to Tavro
          </Link>
          <Link href="/" className="flex items-center gap-2">
            <div className="w-7 h-7 bg-amber-500 rounded-lg flex items-center justify-center">
              <ChefHat className="w-4 h-4 text-charcoal-900" />
            </div>
            <span className="text-lg font-bold font-display tracking-tight text-charcoal-900">Tavro</span>
          </Link>
        </div>
      </nav>

      <div className="flex-1 max-w-6xl mx-auto px-6 py-12 w-full flex gap-12">
        {/* Sidebar — desktop only */}
        <aside className="hidden lg:block w-56 shrink-0">
          <nav className="sticky top-24 space-y-1">
            <p className="text-xs font-semibold text-charcoal-400 uppercase tracking-wider mb-3 px-3">Legal</p>
            {legalPages.map((page) => {
              const active = pathname === page.href;
              return (
                <Link
                  key={page.href}
                  href={page.href}
                  className={`block px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                    active
                      ? "bg-amber-50 text-amber-800 border border-amber-200"
                      : "text-charcoal-600 hover:text-charcoal-900 hover:bg-charcoal-50"
                  }`}
                >
                  {page.label}
                </Link>
              );
            })}
          </nav>
        </aside>

        {/* Mobile page selector */}
        <div className="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-charcoal-100 z-40 px-4 py-2 flex gap-2 overflow-x-auto">
          {legalPages.map((page) => {
            const active = pathname === page.href;
            return (
              <Link
                key={page.href}
                href={page.href}
                className={`shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors ${
                  active
                    ? "bg-amber-500 text-charcoal-900"
                    : "bg-charcoal-100 text-charcoal-600 hover:bg-charcoal-200"
                }`}
              >
                {page.label}
              </Link>
            );
          })}
        </div>

        {/* Main content */}
        <main className="flex-1 min-w-0 pb-24 lg:pb-0">
          {children}
        </main>
      </div>

      {/* Footer */}
      <footer className="bg-charcoal-950 py-8 border-t border-charcoal-800">
        <div className="max-w-6xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4">
          <div className="flex items-center gap-2">
            <div className="w-6 h-6 bg-amber-500 rounded-md flex items-center justify-center">
              <ChefHat className="w-3.5 h-3.5 text-charcoal-900" />
            </div>
            <span className="text-sm font-bold font-display text-white tracking-tight">Tavro</span>
          </div>
          <div className="flex flex-wrap items-center justify-center gap-4 text-xs text-charcoal-400">
            {legalPages.map((page) => (
              <Link key={page.href} href={page.href} className="hover:text-white transition-colors">
                {page.label}
              </Link>
            ))}
          </div>
          <p className="text-charcoal-500 text-xs">
            &copy; {new Date().getFullYear()} Tavro Technologies
          </p>
        </div>
      </footer>
    </div>
  );
}
