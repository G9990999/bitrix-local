import { useState, useEffect } from 'react'
import {
  Table, TableHeader, TableColumn, TableBody, TableRow, TableCell,
  Chip, Button, Input, Spinner,
} from '@heroui/react'

interface EventItem {
  id: number
  event: string
  data: any
  ts: any
}

export default function EventsPage() {
  const [events, setEvents] = useState<EventItem[]>([])
  const [loading, setLoading] = useState(true)
  const [isPopping, setIsPopping] = useState(false)
  const [search, setSearch] = useState('')

  // 1. Просто чтение текущей БД (БЕЗ УДАЛЕНИЯ)
  const fetchEvents = () => {
    setLoading(true)
    fetch('/api/events') // Ссылается на bx:webhook-reg или прямой SELECT
      .then((r) => r.json())
      .then((res) => {
        // Если в ответе пришел список из b_rest_event или очереди
        const items = res.items || res.data?.items || [];
        setEvents(items);
      })
      .catch(() => setEvents([]))
      .finally(() => setLoading(false))
  }

  // 2. Извлечение НОВЫХ (Атомарный POP с удалением)
const handlePop = () => {
  setIsPopping(true);
  fetch('/api/events/pop')
    .then((r) => r.json())
    .then((res) => {
       // Просто заменяем стейт тем, что пришло. 
       // Раз база очистилась, то на экране теперь только "свежевыжатые" данные.
       if (res.status === 'ok') setEvents(res.items || []);
    })
    .finally(() => setIsPopping(false));
};

  // 3. Имитация записи в БД
const addTestEvent = () => {
  fetch('/api/events', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      event: 'ONCRMDEALADD', // Имитируем реальный хук
      data: { ID: Math.floor(Math.random() * 1000), TITLE: "Новая сделка" }
    })
  }).then(() => fetchEvents());
};

  useEffect(() => {
    fetchEvents(); // При загрузке только смотрим
  }, [])

  const filtered = events.filter(e => 
    String(e.event || '').toLowerCase().includes(search.toLowerCase())
  )

  return (
    <div className="p-6 max-w-7xl mx-auto">
      <div className="flex justify-between items-end mb-8 gap-4">
        <div>
          <h1 className="text-3xl font-bold text-slate-900">Bitrix Event Monitor</h1>
          <p className="text-slate-500 mt-1">Режим: Просмотр (Fetch) + Извлечение (Pop)</p>
        </div>
        <div className="flex gap-3">
          <Button color="secondary" variant="flat" isLoading={isPopping} onPress={handlePop}>
            Pop (Забрать из БД)
          </Button>
          <Button color="primary" onPress={addTestEvent}>
            + Записать в БД
          </Button>
        </div>
      </div>

      <Input
        isClearable
        placeholder="Поиск по событию..."
        value={search}
        onValueChange={setSearch}
        className="mb-6 max-w-md"
      />

      <Table aria-label="Events Table">
        <TableHeader>
          <TableColumn>ID</TableColumn>
          <TableColumn>СОБЫТИЕ</TableColumn>
          <TableColumn>ДАННЫЕ</TableColumn>
          <TableColumn>TIMESTAMP</TableColumn>
        </TableHeader>
        <TableBody 
          emptyContent={loading ? <Spinner /> : "В базе данных пусто"}
          items={filtered}
        >
          {(item) => (
            <TableRow key={item.id}>
              <TableCell className="font-mono text-slate-400">#{item.id}</TableCell>
              <TableCell>
                <Chip size="sm" variant="dot" color="primary">{String(item.event)}</Chip>
              </TableCell>
              <TableCell className="font-mono text-[10px]">
                {typeof item.data === 'object' ? JSON.stringify(item.data) : String(item.data)}
              </TableCell>
              <TableCell className="text-slate-500 text-xs">
                {typeof item.ts === 'object' ? JSON.stringify(item.ts) : String(item.ts || '—')}
              </TableCell>
            </TableRow>
          )}
        </TableBody>
      </Table>
    </div>
  )
}
