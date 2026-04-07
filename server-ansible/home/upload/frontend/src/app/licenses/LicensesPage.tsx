import { useState, useEffect } from 'react'
import {
  Table, TableHeader, TableColumn, TableBody, TableRow, TableCell,
  Progress, Spinner,
} from '@heroui/react'

interface License {
  ID: number
  NAME: string
  SEATS: number
  SEATS_USED: number
  PURCHASE_DATE: string
}

export default function LicensesPage() {
  const [licenses, setLicenses] = useState<License[]>([])
  const [loading, setLoading]   = useState(true)

  useEffect(() => {
    fetch('/api/snipeit/licenses')
      .then((r) => r.json())
      .then((data) => setLicenses(data.items ?? []))
      .catch(() => setLicenses([]))
      .finally(() => setLoading(false))
  }, [])

  return (
    <div>
      <h1 className="text-2xl font-bold mb-4">Лицензии</h1>

      {loading ? (
        <div className="flex justify-center py-10"><Spinner /></div>
      ) : (
        <Table aria-label="Список лицензий">
          <TableHeader>
            <TableColumn>ID</TableColumn>
            <TableColumn>Название</TableColumn>
            <TableColumn>Мест всего</TableColumn>
            <TableColumn>Мест использовано</TableColumn>
            <TableColumn>Использование</TableColumn>
            <TableColumn>Дата покупки</TableColumn>
          </TableHeader>
          <TableBody emptyContent="Лицензии не найдены">
            {licenses.map((lic) => {
              const pct = lic.SEATS > 0 ? Math.round((lic.SEATS_USED / lic.SEATS) * 100) : 0
              return (
                <TableRow key={lic.ID}>
                  <TableCell>{lic.ID}</TableCell>
                  <TableCell>{lic.NAME}</TableCell>
                  <TableCell>{lic.SEATS}</TableCell>
                  <TableCell>{lic.SEATS_USED}</TableCell>
                  <TableCell>
                    <Progress
                      size="sm"
                      value={pct}
                      color={pct >= 90 ? 'danger' : pct >= 70 ? 'warning' : 'success'}
                      label={`${pct}%`}
                      showValueLabel
                    />
                  </TableCell>
                  <TableCell>{lic.PURCHASE_DATE ?? '—'}</TableCell>
                </TableRow>
              )
            })}
          </TableBody>
        </Table>
      )}
    </div>
  )
}
