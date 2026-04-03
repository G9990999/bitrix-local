import { useState, useEffect } from 'react'
import {
  Table, TableHeader, TableColumn, TableBody, TableRow, TableCell,
  Chip, Spinner,
} from '@heroui/react'

interface Role {
  role_name: string
  department: string
  description: string
  access_count: number
}

export default function RolesPage() {
  const [roles, setRoles]   = useState<Role[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetch('/api/roles')
      .then((r) => r.json())
      .then((data) => setRoles(data.roles ?? []))
      .catch(() => setRoles([]))
      .finally(() => setLoading(false))
  }, [])

  return (
    <div>
      <h1 className="text-2xl font-bold mb-2">Роли и доступы</h1>
      <p className="text-sm text-gray-500 mb-4">
        Каталог должностей с матрицей прав. Доступы выдаются ролям, а не людям.
        Команды CLI: <code className="bg-gray-100 px-1 rounded">bx bx:role install</code>,{' '}
        <code className="bg-gray-100 px-1 rounded">bx bx:role generator</code>
      </p>

      {loading ? (
        <div className="flex justify-center py-10"><Spinner /></div>
      ) : (
        <Table aria-label="Список ролей">
          <TableHeader>
            <TableColumn>Должность</TableColumn>
            <TableColumn>Отдел</TableColumn>
            <TableColumn>Описание</TableColumn>
            <TableColumn>Прав доступа</TableColumn>
          </TableHeader>
          <TableBody emptyContent="Запустите: bx bx:role install">
            {roles.map((role) => (
              <TableRow key={role.role_name}>
                <TableCell className="font-medium">{role.role_name}</TableCell>
                <TableCell>{role.department || '—'}</TableCell>
                <TableCell className="text-sm text-gray-600">{role.description || '—'}</TableCell>
                <TableCell>
                  <Chip
                    size="sm"
                    color={Number(role.access_count) > 0 ? 'primary' : 'default'}
                    variant="flat"
                  >
                    {role.access_count}
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
