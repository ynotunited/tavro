"use client";

import { CheckCircle2, Circle } from "lucide-react";
import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import api from "@/lib/axios";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/Card";
import { Progress } from "@/components/ui/Progress";

interface SetupStep {
  id: string;
  title: string;
  description: string;
  href: string;
  completed: boolean;
}

export function SetupChecklist() {
  const { data: products = [], isLoading: isLoadingProducts } = useQuery({
    queryKey: ["products"],
    queryFn: async () => {
      const res = await api.get("/products");
      return res.data.data;
    },
  });

  const { data: tables = [], isLoading: isLoadingTables } = useQuery({
    queryKey: ["tables"],
    queryFn: async () => {
      const res = await api.get("/tables");
      return res.data.data;
    },
  });

  const { data: activeShift = null, isLoading: isLoadingShifts } = useQuery({
    queryKey: ["shifts-active"],
    queryFn: async () => {
      const res = await api.get("/shifts/active");
      return res.data.data;
    },
  });

  const { data: orders = [], isLoading: isLoadingOrders } = useQuery({
    queryKey: ["orders"],
    queryFn: async () => {
      const res = await api.get("/orders");
      return res.data.data;
    },
  });

  const steps: SetupStep[] = [
    {
      id: "menu",
      title: "Add your menu items",
      description: "Create categories and add your first products.",
      href: "/menu",
      completed: products.length > 0,
    },
    {
      id: "tables",
      title: "Set up your floorplan",
      description: "Map out your tables to take dine-in orders.",
      href: "/floorplan",
      completed: tables.length > 0,
    },
    {
      id: "shift",
      title: "Open your first shift",
      description: "Start a shift to begin accepting payments.",
      href: "/shifts",
      completed: activeShift != null,
    },
    {
      id: "order",
      title: "Take your first order",
      description: "Head to the POS and process a transaction.",
      href: "/pos",
      completed: orders.length > 0,
    },
  ];

  if (isLoadingProducts || isLoadingTables || isLoadingShifts || isLoadingOrders) {
    return null;
  }

  const completedCount = steps.filter((s) => s.completed).length;
  const progress = (completedCount / steps.length) * 100;

  if (completedCount === steps.length) {
    return null;
  }

  return (
    <Card className="mb-8 border-amber-500/20 shadow-md">
      <CardHeader className="bg-amber-50/50 pb-4">
        <CardTitle className="text-lg font-medium text-charcoal-900 flex items-center justify-between">
          <span>Let&apos;s get your business ready</span>
          <span className="text-sm text-charcoal-500">
            {completedCount} of {steps.length} completed
          </span>
        </CardTitle>
        <Progress value={progress} className="h-2 bg-charcoal-100 mt-2">
          <div
            className="h-full bg-amber-500 transition-all"
            style={{ width: `${progress}%` }}
          />
        </Progress>
      </CardHeader>
      <CardContent className="p-0">
        <div className="divide-y divide-charcoal-100">
          {steps.map((step) => (
            <Link
              key={step.id}
              href={step.href}
              className={`flex items-start gap-4 p-4 hover:bg-charcoal-50 transition-colors ${
                step.completed ? "opacity-60" : ""
              }`}
            >
              <div className="mt-0.5">
                {step.completed ? (
                  <CheckCircle2 className="h-5 w-5 text-green-500" />
                ) : (
                  <Circle className="h-5 w-5 text-charcoal-300" />
                )}
              </div>
              <div>
                <p
                  className={`font-medium ${
                    step.completed ? "text-charcoal-500 line-through" : "text-charcoal-900"
                  }`}
                >
                  {step.title}
                </p>
                <p className="text-sm text-charcoal-500">{step.description}</p>
              </div>
            </Link>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}