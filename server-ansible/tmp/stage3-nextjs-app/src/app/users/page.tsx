'use client';

import {
  Table,
  TableHeader,
  TableColumn,
  TableBody,
  TableRow,
  TableCell,
  Input,
  Spinner,
} from '@heroui/react';
import { useEffect, useState } from 'react';
import { getUsers } from '@/lib/api';
import type { User } from '@/types';

const COLUMNS = [
  { key: 'id',       label: 'ID'       },
  { key: 'name',     label: 'ФИО'      },
  { key: 'username', label: 'Логин'    },
  { key: 'email',    label: 'Email'    },
  { key: 'jobtitle', label: 'Должность'},
  { key: 'phone',    label: 'Телефон'  },
];

export default function UsersPage() {
  const [users, setUsers]     = useState<User[]>([]);
  const [total, setTotal]     = useState(0);
  const [search, setSearch]   = useState('');
  const [loading, setLoading] = useState(false);

  const load = async () => {
    setLoading(true);
    try {
      const res = await getUsers({ search, limit: 100 });
      setUsers(res.data);
      setTotal(res.total);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { load(); }, [search]);

  const renderCell = (user: User, key: string) => {
    switch (key) {
      case 'id':       return user.id;
      case 'name':     return `${user.last_name ?? ''} ${user.first_name ?? ''}`.trim() || '—';
      case 'username': return <span className="font-mono text-sm">{user.username}</span>;
      case 'email':    return user.email ?? '—';
      case 'jobtitle': return user.jobtitle ?? '—';
      case 'phone':    return user.phone ?? '—';
      default:         return null;
    }
  };

  return (
    <div>
      <h1 className="text-2xl font-bold mb-6">Пользователи</h1>

      <div className="flex gap-3 items-center mb-4">
        <Input
          className="max-w-xs"
          placeholder="Поиск по имени или логину…"
          value={search}
          onValueChange={setSearch}
          isClearable
          onClear={() => setSearch('')}
        />
      </div>

      <Table aria-label="Пользователи">
        <TableHeader columns={COLUMNS}>
          {(col) => <TableColumn key={col.key}>{col.label}</TableColumn>}
        </TableHeader>
        <TableBody
          items={users}
          isLoading={loading}
          loadingContent={<Spinner label="Загрузка…" />}
          emptyContent="Пользователей не найдено"
        >
          {(user) => (
            <TableRow key={user.id}>
              {(key) => <TableCell>{renderCell(user, String(key))}</TableCell>}
            </TableRow>
          )}
        </TableBody>
      </Table>

      <p className="text-small text-default-400 mt-2">Всего: {total}</p>
    </div>
  );
}
