'use client';

import { HeroUIProvider } from '@heroui/react';
import { useRouter } from 'next/navigation';
import AppNavbar from '@/components/AppNavbar';

export function Providers({ children }: { children: React.ReactNode }) {
  const router = useRouter();

  return (
    <HeroUIProvider navigate={router.push}>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
        <AppNavbar />
        <main className="max-w-7xl mx-auto px-4 py-6">{children}</main>
      </div>
    </HeroUIProvider>
  );
}
