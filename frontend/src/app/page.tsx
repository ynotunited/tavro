"use client";

import Link from "next/link";
import { ArrowRight, ChefHat, LineChart, WifiOff, LayoutDashboard, CreditCard, Box, CheckCircle2 } from "lucide-react";
import { Button } from "@/components/ui/Button";

export default function LandingPage() {
  const features = [
    {
      icon: <LayoutDashboard className="w-6 h-6 text-amber-500" />,
      title: "Fast POS",
      description: "Take orders and process payments with a lightning-fast, tablet-optimized interface.",
    },
    {
      icon: <ChefHat className="w-6 h-6 text-amber-500" />,
      title: "Kitchen Display",
      description: "Send orders straight to the kitchen. No more lost paper tickets or missed modifiers.",
    },
    {
      icon: <WifiOff className="w-6 h-6 text-amber-500" />,
      title: "Offline Mode",
      description: "Internet down? Keep taking orders. Tavro syncs automatically when you're back online.",
    },
    {
      icon: <LineChart className="w-6 h-6 text-amber-500" />,
      title: "Real-time Reports",
      description: "Track sales, top products, and staff performance from anywhere in the world.",
    },
    {
      icon: <Box className="w-6 h-6 text-amber-500" />,
      title: "Inventory",
      description: "Manage stock levels, track wastage, and automate purchase orders effortlessly.",
    },
    {
      icon: <CreditCard className="w-6 h-6 text-amber-500" />,
      title: "Split Payments",
      description: "Handle complex bills with ease. Split by amount, cash, or card seamlessly.",
    }
  ];

  const plans = [
    {
      name: "Starter",
      price: "₦15,000",
      features: ["1 Branch", "3 Staff Accounts", "1 POS Terminal", "Basic Reports"],
    },
    {
      name: "Growth",
      price: "₦35,000",
      features: ["3 Branches", "15 Staff Accounts", "5 POS Terminals", "Advanced Inventory", "Priority Support"],
      highlighted: true,
    },
    {
      name: "Pro",
      price: "₦75,000",
      features: ["10 Branches", "50 Staff Accounts", "20 POS Terminals", "API Access", "Dedicated Account Manager"],
    }
  ];

  return (
    <div className="min-h-screen flex flex-col font-sans bg-white selection:bg-amber-500/30">
      
      {/* Navigation */}
      <nav className="border-b border-charcoal-100 bg-white/80 backdrop-blur-md sticky top-0 z-50">
        <div className="max-w-6xl mx-auto px-6 h-20 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 bg-amber-500 rounded-lg flex items-center justify-center">
              <ChefHat className="w-5 h-5 text-charcoal-900" />
            </div>
            <span className="text-xl font-bold font-display tracking-tight text-charcoal-900">Tavro</span>
          </div>
          <div className="flex items-center gap-4">
            <Link href="/login" className="text-sm font-medium text-charcoal-600 hover:text-charcoal-900 transition-colors hidden sm:block">
              Log In
            </Link>
            <Button asChild className="bg-charcoal-900 hover:bg-charcoal-800 text-white rounded-full px-6">
              <Link href="/login">Get Started</Link>
            </Button>
          </div>
        </div>
      </nav>

      {/* Hero Section */}
      <section className="relative pt-24 pb-32 overflow-hidden bg-charcoal-50">
        <div className="absolute inset-0 bg-[url('/noise.png')] opacity-20 mix-blend-overlay"></div>
        <div className="max-w-6xl mx-auto px-6 relative z-10 text-center">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100/50 border border-amber-200 text-amber-800 text-sm font-medium mb-8">
            <span className="relative flex h-2 w-2">
              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
              <span className="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
            </span>
            Tavro v1.0 is now live
          </div>
          <h1 className="text-5xl md:text-7xl font-bold font-display text-charcoal-900 tracking-tight leading-tight mb-6 max-w-4xl mx-auto">
            The operating system for <br className="hidden md:block"/> 
            <span className="text-amber-500">African restaurants.</span>
          </h1>
          <p className="text-lg md:text-xl text-charcoal-600 mb-10 max-w-2xl mx-auto leading-relaxed">
            Premium Point of Sale, Kitchen Display, and Inventory management built specifically for the realities of running a hospitality business in Africa.
          </p>
          <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
            <Button asChild size="lg" className="h-14 px-8 text-base bg-amber-500 hover:bg-amber-600 text-charcoal-900 font-bold rounded-full w-full sm:w-auto shadow-lg shadow-amber-500/20">
              <Link href="/login">Start 14-Day Free Trial</Link>
            </Button>
            <Button asChild variant="outline" size="lg" className="h-14 px-8 text-base border-charcoal-200 text-charcoal-700 hover:bg-charcoal-100 rounded-full w-full sm:w-auto">
              <Link href="#features">Explore Features</Link>
            </Button>
          </div>
        </div>
      </section>

      {/* Features Grid */}
      <section id="features" className="py-24 bg-white">
        <div className="max-w-6xl mx-auto px-6">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold font-display text-charcoal-900 mb-4">Everything you need to scale</h2>
            <p className="text-charcoal-600 max-w-2xl mx-auto text-lg">Replace disjointed systems with one unified platform that talks to every part of your business.</p>
          </div>
          
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {features.map((feature, i) => (
              <div key={i} className="p-8 rounded-2xl bg-charcoal-50 border border-charcoal-100 hover:border-amber-200 transition-colors group">
                <div className="w-12 h-12 rounded-xl bg-white flex items-center justify-center shadow-sm mb-6 group-hover:scale-110 transition-transform">
                  {feature.icon}
                </div>
                <h3 className="text-xl font-bold text-charcoal-900 mb-3">{feature.title}</h3>
                <p className="text-charcoal-600 leading-relaxed">{feature.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Pricing Section */}
      <section className="py-24 bg-charcoal-900 text-white">
        <div className="max-w-6xl mx-auto px-6">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold font-display mb-4">Simple, transparent pricing</h2>
            <p className="text-charcoal-300 max-w-2xl mx-auto text-lg">No hidden fees. Upgrade or downgrade at any time.</p>
          </div>

          <div className="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            {plans.map((plan, i) => (
              <div key={i} className={`p-8 rounded-3xl ${plan.highlighted ? 'bg-amber-500 text-charcoal-900 scale-105 shadow-2xl' : 'bg-charcoal-800 border border-charcoal-700'}`}>
                <h3 className="text-2xl font-bold mb-2">{plan.name}</h3>
                <div className="flex items-baseline gap-1 mb-8">
                  <span className="text-4xl font-bold tracking-tight">{plan.price}</span>
                  <span className={`text-sm ${plan.highlighted ? 'text-charcoal-700' : 'text-charcoal-400'}`}>/mo</span>
                </div>
                
                <ul className="space-y-4 mb-8">
                  {plan.features.map((feat, j) => (
                    <li key={j} className="flex items-center gap-3">
                      <CheckCircle2 className={`w-5 h-5 ${plan.highlighted ? 'text-charcoal-900' : 'text-amber-500'}`} />
                      <span className={plan.highlighted ? 'font-medium' : 'text-charcoal-200'}>{feat}</span>
                    </li>
                  ))}
                </ul>

                <Button 
                  asChild
                  variant={plan.highlighted ? 'default' : 'outline'} 
                  className={`w-full h-12 rounded-full text-base ${plan.highlighted ? 'bg-charcoal-900 text-white hover:bg-charcoal-800' : 'border-charcoal-600 hover:bg-charcoal-700'}`}
                >
                  <Link href="/login">Get Started</Link>
                </Button>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-charcoal-950 py-12 border-t border-charcoal-800">
        <div className="max-w-6xl mx-auto px-6">
          <div className="flex flex-col md:flex-row items-start justify-between gap-8 mb-8">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 bg-amber-500 rounded-lg flex items-center justify-center">
                <ChefHat className="w-5 h-5 text-charcoal-900" />
              </div>
              <span className="text-xl font-bold font-display text-white tracking-tight">Tavro</span>
            </div>
            <div className="grid grid-cols-2 sm:grid-cols-3 gap-x-12 gap-y-3 text-sm">
              <Link href="/privacy" className="text-charcoal-400 hover:text-white transition-colors">Privacy Policy</Link>
              <Link href="/terms" className="text-charcoal-400 hover:text-white transition-colors">Terms of Use</Link>
              <Link href="/compliance" className="text-charcoal-400 hover:text-white transition-colors">Data &amp; Compliance</Link>
              <Link href="/ip-infringement" className="text-charcoal-400 hover:text-white transition-colors">IP Infringement</Link>
              <Link href="/acceptable-use" className="text-charcoal-400 hover:text-white transition-colors">Acceptable Use</Link>
              <Link href="/cookies" className="text-charcoal-400 hover:text-white transition-colors">Cookie Policy</Link>
            </div>
          </div>
          <div className="border-t border-charcoal-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p className="text-charcoal-500 text-xs">
              &copy; {new Date().getFullYear()} Tavro Technologies. All rights reserved.
            </p>
            <p className="text-charcoal-600 text-xs">
              Built for African restaurants.
            </p>
          </div>
        </div>
      </footer>
    </div>
  );
}
