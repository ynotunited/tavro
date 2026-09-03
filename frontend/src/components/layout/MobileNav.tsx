"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { LayoutDashboard, ShoppingCart, Box, Settings } from "lucide-react";
import { useAuthStore } from "@/store/authStore";

export function MobileNav() {
  const pathname = usePathname();
  const user = useAuthStore(state => state.user);
  
  if (!user) return null;

  const role = user.role ?? "staff";
  const isManager = ["admin", "owner", "manager", "supervisor"].includes(role);

  const links = [
    {
      href: "/dashboard",
      icon: <LayoutDashboard className="w-6 h-6" />,
      label: "Dashboard",
      visible: isManager,
    },
    {
      href: "/pos",
      icon: <ShoppingCart className="w-6 h-6" />,
      label: "POS",
      visible: true,
    },
    {
      href: "/inventory",
      icon: <Box className="w-6 h-6" />,
      label: "Inventory",
      visible: isManager,
    },
    {
      href: "/settings/team",
      icon: <Settings className="w-6 h-6" />,
      label: "Settings",
      visible: isManager,
    },
  ];

  const visibleLinks = links.filter((link) => link.visible);

  return (
    <nav className="sm:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-charcoal-100 pb-[env(safe-area-inset-bottom)] z-50">
      <div className="flex justify-around items-center h-16">
        {visibleLinks.map((link) => {
          const isActive = pathname.startsWith(link.href);
          return (
            <Link
              key={link.href}
              href={link.href}
              className={`flex flex-col items-center justify-center w-full h-full space-y-1 transition-colors ${
                isActive ? "text-amber-500" : "text-charcoal-400 hover:text-charcoal-600"
              }`}
            >
              {link.icon}
              <span className="text-[10px] font-medium leading-none">{link.label}</span>
            </Link>
          );
        })}
      </div>
    </nav>
  );
}
