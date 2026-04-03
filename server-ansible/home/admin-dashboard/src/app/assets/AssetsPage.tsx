import { useState, useEffect } from 'react'
import {
  Table, TableHeader, TableColumn, TableBody, TableRow, TableCell,
  Chip, Button, Input, Spinner,
} from '@heroui/react'

interface Asset {
  ID: number
  NAME: string
  ASSET_TAG: string
  SERIAL: string
  STATUS: 'deployable' | 'deployed' | 'pending' | 'archived'
  DATE_CREATE: string
}

const statusColors: Record<string, 'success' | 'primary' | 'warning' | 'default'> = {
  deployable: 'success',
  deployed:   'primary',
  pending:    'warning',
  archived:   'default',
}

const statusLabels: Record<string, string> = {
  deployable: 'Доступен',
  deployed:   'Выдан',
  pending:    'Ожидание',
  archived:   'Архив',
}

export default function AssetsPage() {
  const [assets, setAssets]     = useState<Asset[]>([])
  const [loading, setLoading]   = useState(true)
  const [search, setSearch]     = useState('')

  useEffect(() => {
    fetch('/api/snipeit/assets')
      .then((r) => r.json())
      .then((data) => setAssets(data.items ?? []))
      .catch(() => setAssets([]))
      .finally(() => setLoading(false))
  }, [])

  const filtered = assets.filter(
    (a) =>
      a.NAME?.toLowerCase().includes(search.toLowerCase()) ||
      a.ASSET_TAG?.toLowerCase().includes(search.toLowerCase()) ||
      a.SERIAL?.toLowerCase().includes(search.toLowerCase())
  )

  return (
    <div>
      <div className="flex justify-between items-center mb-4">
        <h1 className="text-2xl font-bold">Активы</h1>
        <Button color="primary" size="sm">+ Добавить актив</Button>
      </div>

      <Input
        placeholder="Поиск по названию, тегу, серийному номеру..."
        value={search}
        onValueChange={setSearch}
        className="mb-4 max-w-md"
      />

      {loading ? (
        <div className="flex justify-center py-10"><Spinner /></div>
      ) : (
        <Table aria-label="Список активов">
          <TableHeader>
            <TableColumn>ID</TableColumn>
            <TableColumn>Название</TableColumn>
            <TableColumn>Asset Tag</TableColumn>
            <TableColumn>Серийный номер</TableColumn>
            <TableColumn>Статус</TableColumn>
            <TableColumn>Создан</TableColumn>
          </TableHeader>
          <TableBody emptyContent="Активы не найдены">
            {filtered.map((asset) => (
              <TableRow key={asset.ID}>
                <TableCell>{asset.ID}</TableCell>
                <TableCell>{asset.NAME}</TableCell>
                <TableCell>{asset.ASSET_TAG || '—'}</TableCell>
                <TableCell>{asset.SERIAL || '—'}</TableCell>
                <TableCell>
                  <Chip
                    size="sm"
                    color={statusColors[asset.STATUS] ?? 'default'}
                    variant="flat"
                  >
                    {statusLabels[asset.STATUS] ?? asset.STATUS}
                  </Chip>
                </TableCell>
                <TableCell>{asset.DATE_CREATE?.slice(0, 10) ?? '—'}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}
    </div>
  )
}
