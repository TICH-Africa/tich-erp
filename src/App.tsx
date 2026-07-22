import { useState } from 'react'
import Landing from '@/pages/Landing'
import Login from '@/pages/Login'
import ProgramsPage from '@/pages/Programs'
import AdminShell from '@/pages/admin/AdminShell'
import { renderDashboard } from '@/pages/admin/dashboards/index'
import HRPages from '@/pages/admin/hr/HRPages'
import FinancePages from '@/pages/admin/finance/FinancePages'
import AcademicsPages from '@/pages/admin/academics/AcademicsPages'
import AdmissionsPages from '@/pages/admin/admissions/AdmissionsPages'
import ProcurementShell from '@/pages/procurement/ProcurementShell'
import SaccoShell from '@/pages/sacco/SaccoShell'
import { type User, type Role } from '@/data/mock'

type AppPage = 'landing' | 'login' | 'admin' | 'programs' | 'hr' | 'procurement' | 'sacco'

interface ModuleUser {
  name: string
  email: string
  avatar: string
  role: Role
}

export default function App() {
  const [page, setPage] = useState<AppPage>('landing')
  const [user, setUser] = useState<User | null>(null)
  const [procurementUser, setProcurementUser] = useState<ModuleUser | null>(null)
  const [saccoUser, setSaccoUser] = useState<ModuleUser | null>(null)

  const handleLogin = (u: User) => {
    setUser(u)
    setPage('admin')
  }

  const handleLogout = () => {
    setUser(null)
    setPage('landing')
  }

  const handleModuleLogin = (u: User, module: 'procurement' | 'sacco') => {
    if (module === 'procurement') {
      setProcurementUser({ name: u.name, email: u.email, avatar: u.avatar, role: u.role })
      setPage('procurement')
    } else {
      setSaccoUser({ name: u.name, email: u.email, avatar: u.avatar, role: u.role })
      setPage('sacco')
    }
  }

  if (page === 'landing') {
    return <Landing onLogin={() => setPage('login')} onViewAllPrograms={() => setPage('programs')} />
  }

  if (page === 'login') {
    return <Login onLogin={handleLogin} onBack={() => setPage('landing')} onModuleLogin={handleModuleLogin} />
  }

  if (page === 'programs') {
    return <ProgramsPage onBack={() => setPage('landing')} />
  }

  if (page === 'procurement' && procurementUser) {
    return <ProcurementShell user={procurementUser} onLogout={() => { setProcurementUser(null); setPage('landing') }} />
  }

  if (page === 'sacco' && saccoUser) {
    return <SaccoShell user={saccoUser} onLogout={() => { setSaccoUser(null); setPage('landing') }} />
  }

  if (page === 'admin' && user) {
    return (
      <AdminShell user={user} onLogout={handleLogout}>
        {(activePage) => {
          if (activePage.startsWith('hr-')) {
            const subPage = activePage.replace('hr-', '') as 'overview' | 'employees' | 'onboarding' | 'leave' | 'attendance' | 'payroll' | 'expenses' | 'documents' | 'reports' | 'training' | 'analytics'
            return <HRPages key={subPage} initialSubPage={subPage} />
          }
          if (activePage.startsWith('finance-')) {
            const subPage = activePage.replace('finance-', '') as 'overview' | 'invoices' | 'bills' | 'banking' | 'accounts' | 'reports' | 'customers' | 'vendors' | 'journal'
            return <FinancePages key={subPage} initialSubPage={subPage} />
          }
          if (activePage.startsWith('admissions-')) {
            const subPage = activePage.replace('admissions-', '') as 'overview' | 'applications' | 'reviews' | 'shortlisted' | 'offers' | 'registered'
            return <AdmissionsPages key={subPage} initialSubPage={subPage} />
          }
          if (activePage.startsWith('academics-')) {
            const subPage = activePage.replace('academics-', '') as 'overview' | 'students' | 'programs' | 'admissions' | 'staff' | 'examinations' | 'timetable' | 'departments' | 'records' | 'courses'
            return <AcademicsPages key={subPage} initialSubPage={subPage} />
          }
          return renderDashboard(user.role, activePage)
        }}
      </AdminShell>
    )
  }

  return null
}
