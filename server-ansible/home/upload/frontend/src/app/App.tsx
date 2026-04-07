import { Routes, Route } from 'react-router-dom'
import Navbar from '../components/Navbar'
import Dashboard from './Dashboard'
import AssetsPage from './assets/AssetsPage'
import LicensesPage from './licenses/LicensesPage'
import UsersPage from './users/UsersPage'
import RolesPage from './roles/RolesPage'
import AuditPage from './audit/AuditPage'
import EventsPage from './events/EventsPage'

export default function App() {
  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <Navbar />
      <main className="container mx-auto px-4 py-6">
        <Routes>
          <Route path="/"          element={<Dashboard />} />
          <Route path="/assets"    element={<AssetsPage />} />
          <Route path="/licenses"  element={<LicensesPage />} />
          <Route path="/users"     element={<UsersPage />} />
          <Route path="/roles"     element={<RolesPage />} />
          <Route path="/audit"     element={<AuditPage />} />
          <Route path="/events"     element={<EventsPage />} />
        </Routes>
      </main>
    </div>
  )
}
