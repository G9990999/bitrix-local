import { useState, useEffect } from 'react'
import {
  Table, TableHeader, TableColumn, TableBody, TableRow, TableCell,
  Chip, Spinner,
} from '@heroui/react'

interface User {
  ID: number
  NAME: string
  LAST_NAME: string
  EMAIL: string
  ACTIVE: string
}

export default function UsersPage() {
  const [users, setUsers]   = useState<User[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetch('/api/bitrix/users')
      .then((r) => r.json())
      .then((data) => setUsers(data.items ?? []))
      .catch(() => setUsers([]))
      .finally(() => setLoading(false))
  }, [])

  return (
    <div>
      <h1 className="text-2xl font-bold mb-4">Пользователи</h1>

      {loading ? (
        <div className="flex justify-center py-10"><Spinner /></div>
      ) : (
        <Table aria-label="Список пользователей">
          <TableHeader>
            <TableColumn>ID</TableColumn>
            <TableColumn>Имя</TableColumn>
            <TableColumn>Фамилия</TableColumn>
            <TableColumn>Email</TableColumn>
            <TableColumn>Статус</TableColumn>
          </TableHeader>
          <TableBody emptyContent="Пользователи не найдены">
            {users.map((user) => (
              <TableRow key={user.ID}>
                <TableCell>{user.ID}</TableCell>
                <TableCell>{user.NAME}</TableCell>
                <TableCell>{user.LAST_NAME}</TableCell>
                <TableCell>{user.EMAIL}</TableCell>
                <TableCell>
                  <Chip
                    size="sm"
                    color={user.ACTIVE === 'Y' ? 'success' : 'danger'}
                    variant="flat"
                  >
                    {user.ACTIVE === 'Y' ? 'Активен' : 'Отключён'}
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
