import { useState, useEffect } from 'react'
import {
  Table, TableHeader, TableColumn, TableBody, TableRow, TableCell,
  Chip, Spinner,
} from '@heroui/react'

interface Assignment {
  id: number
  user_id: number
  asset_id: number | null
  license_id: number | null
  assigned_at: string
  returned_at: string | null
}

export default function AuditPage() {
  const [assignments, setAssignments] = useState<Assignment[]>([])
  const [loading, setLoading]         = useState(true)

  useEffect(() => {
    fetch('/api/snipeit/assignments')
      .then((r) => r.json())
      .then((data) => setAssignments(data.items ?? []))
      .catch(() => setAssignments([]))
      .finally(() => setLoading(false))
  }, [])

  return (
    <div>
      <h1 className="text-2xl font-bold mb-2">Аудит доступов</h1>
      <p className="text-sm text-gray-500 mb-4">
        История выдачи и возврата активов и лицензий.
        Позволяет увидеть: кому выдан актив, какие лицензии активны.
      </p>

      {loading ? (
        <div className="flex justify-center py-10"><Spinner /></div>
      ) : (
        <Table aria-label="Аудит назначений">
          <TableHeader>
            <TableColumn>ID</TableColumn>
            <TableColumn>Пользователь ID</TableColumn>
            <TableColumn>Актив ID</TableColumn>
            <TableColumn>Лицензия ID</TableColumn>
            <TableColumn>Выдан</TableColumn>
            <TableColumn>Возвращён</TableColumn>
            <TableColumn>Статус</TableColumn>
          </TableHeader>
          <TableBody emptyContent="Назначений не найдено">
            {assignments.map((a) => (
              <TableRow key={a.id}>
                <TableCell>{a.id}</TableCell>
                <TableCell>{a.user_id}</TableCell>
                <TableCell>{a.asset_id ?? '—'}</TableCell>
                <TableCell>{a.license_id ?? '—'}</TableCell>
                <TableCell>{a.assigned_at?.slice(0, 10) ?? '—'}</TableCell>
                <TableCell>{a.returned_at?.slice(0, 10) ?? '—'}</TableCell>
                <TableCell>
                  <Chip
                    size="sm"
                    color={a.returned_at ? 'default' : 'success'}
                    variant="flat"
                  >
                    {a.returned_at ? 'Возвращён' : 'Активен'}
                  </Chip>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}
    </div>
  )
}
