import AssetTable from '@/components/AssetTable';

export const metadata = { title: 'Активы — Snipe-IT' };

export default function AssetsPage() {
  return (
    <div>
      <h1 className="text-2xl font-bold mb-6">Активы</h1>
      <AssetTable />
    </div>
  );
}
