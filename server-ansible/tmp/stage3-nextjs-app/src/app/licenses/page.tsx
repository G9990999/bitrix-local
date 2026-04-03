'use client';

import { useEffect, useState } from 'react';
import { Spinner } from '@heroui/react';
import LicenseCard from '@/components/LicenseCard';
import { getLicenses } from '@/lib/api';
import type { License } from '@/types';

export default function LicensesPage() {
  const [licenses, setLicenses] = useState<License[]>([]);
  const [loading, setLoading]   = useState(true);
  const [error, setError]       = useState<string | null>(null);

  useEffect(() => {
    getLicenses({ limit: 100 })
      .then((res) => setLicenses(res.data))
      .catch((e: unknown) => setError(e instanceof Error ? e.message : 'Ошибка загрузки'))
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <Spinner className="mt-12 mx-auto block" label="Загрузка…" />;
  if (error)   return <p className="text-danger text-center mt-12">{error}</p>;

  return (
    <div>
      <h1 className="text-2xl font-bold mb-6">Лицензии</h1>

      {licenses.length === 0 ? (
        <p className="text-center text-default-400 py-12">Лицензий не найдено</p>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {licenses.map((lic) => (
            <LicenseCard key={lic.id} license={lic} />
          ))}
        </div>
      )}
    </div>
  );
}
