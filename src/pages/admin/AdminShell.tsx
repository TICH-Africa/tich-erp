import { useState } from 'react'
import logoImg from '@/imports/image.png'
import { type User, ROLES } from '@/data/mock'
import {
  LayoutDashboard, Users, FileCheck, BookOpen, DollarSign, UserCog,
  BarChart3, Settings, Bell, LogOut, ChevronDown, Menu, X,
  ClipboardList, GraduationCap, Star, Calendar, Wallet, Clock, Receipt, FileText,
  Calculator, Landmark, Building2, Download, Award, Mail,
  Shield, AlertTriangle
} from 'lucide-react'

type Page =
  | 'dashboard' | 'approvals' | 'students' | 'staff'
  | 'finance' | 'programs' | 'reports' | 'settings' | 'hr' | 'qa'
  | 'hr-leave' | 'hr-payroll' | 'hr-attendance' | 'hr-expenses' | 'hr-documents' | 'hr-reports' | 'hr-employees'
  | 'finance-overview' | 'finance-invoices' | 'finance-bills' | 'finance-banking' | 'finance-accounts' | 'finance-reports' | 'finance-customers' | 'finance-vendors' | 'finance-journal'
  | 'academics-overview' | 'academics-students' | 'academics-programs' | 'academics-admissions' | 'academics-staff' | 'academics-examinations' | 'academics-timetable' | 'academics-departments' | 'academics-records' | 'academics-courses'
  | 'admissions-overview' | 'admissions-applications' | 'admissions-reviews' | 'admissions-shortlisted' | 'admissions-offers' | 'admissions-registered'
  | 'cms' | 'events'
  | 'qa-overview' | 'qa-quality-plans' | 'qa-training' | 'qa-audits' | 'qa-assessments' | 'qa-self-audits' | 'qa-corrective-actions' | 'qa-reports'

const NAV_BY_ROLE: Record<string, { label: string; page: Page; icon: React.ReactNode }[]> = {
  super_admin: [
    { label: 'Dashboard', page: 'dashboard', icon: <LayoutDashboard size={17} /> },
    { label: 'Website CMS', page: 'cms', icon: <FileText size={17} /> },
    { label: 'Courses', page: 'programs', icon: <BookOpen size={17} /> },
    { label: 'Events', page: 'events', icon: <Calendar size={17} /> },
    { label: 'Users & Roles', page: 'staff', icon: <UserCog size={17} /> },
    { label: 'Settings', page: 'settings', icon: <Settings size={17} /> },
  ],
  ceo: [
    { label: 'Executive Overview', page: 'dashboard', icon: <LayoutDashboard size={17} /> },
    { label: 'Admissions', page: 'admissions-overview', icon: <ClipboardList size={17} /> },
    { label: 'Approvals Override', page: 'approvals', icon: <FileCheck size={17} /> },
    { label: 'Finance Summary', page: 'finance', icon: <DollarSign size={17} /> },
    { label: 'QA Reports', page: 'qa-reports', icon: <BarChart3 size={17} /> },
  ],
  academic_registrar: [
    { label: 'Dashboard', page: 'dashboard', icon: <LayoutDashboard size={17} /> },
    { label: 'Academics', page: 'academics-overview', icon: <BookOpen size={17} /> },
    { label: 'Students', page: 'academics-students', icon: <GraduationCap size={17} /> },
    { label: 'Programs', page: 'academics-programs', icon: <BookOpen size={17} /> },
    { label: 'Admissions', page: 'admissions-overview', icon: <ClipboardList size={17} /> },
    { label: 'Student Records', page: 'academics-records', icon: <Award size={17} /> },
    { label: 'Examinations', page: 'academics-examinations', icon: <FileText size={17} /> },
    { label: 'Timetable', page: 'academics-timetable', icon: <Calendar size={17} /> },
    { label: 'QA', page: 'qa-overview', icon: <Shield size={17} /> },
  ],
  hod: [
    { label: 'Dashboard', page: 'dashboard', icon: <LayoutDashboard size={17} /> },
    { label: 'Academics', page: 'academics-overview', icon: <BookOpen size={17} /> },
    { label: 'Department Students', page: 'academics-students', icon: <GraduationCap size={17} /> },
    { label: 'Faculty', page: 'academics-staff', icon: <Users size={17} /> },
    { label: 'Courses', page: 'academics-courses', icon: <BookOpen size={17} /> },
    { label: 'Timetable', page: 'academics-timetable', icon: <Calendar size={17} /> },
    { label: 'Examinations', page: 'academics-examinations', icon: <FileText size={17} /> },
    { label: 'Self-Audit', page: 'qa-self-audits', icon: <Shield size={17} /> },
    { label: 'Assessments', page: 'qa-assessments', icon: <ClipboardList size={17} /> },
  ],
  admissions_officer: [
    { label: 'Dashboard', page: 'dashboard', icon: <LayoutDashboard size={17} /> },
    { label: 'Overview', page: 'admissions-overview', icon: <BarChart3 size={17} /> },
    { label: 'Applications', page: 'admissions-applications', icon: <ClipboardList size={17} /> },
    { label: 'Pending Reviews', page: 'admissions-reviews', icon: <Clock size={17} /> },
    { label: 'Shortlisted', page: 'admissions-shortlisted', icon: <Star size={17} /> },
    { label: 'Offers', page: 'admissions-offers', icon: <Mail size={17} /> },
    { label: 'Registered', page: 'admissions-registered', icon: <GraduationCap size={17} /> },
    { label: 'QA Verification', page: 'qa-self-audits', icon: <Shield size={17} /> },
  ],
  finance_manager: [
    { label: 'Finance Dashboard', page: 'finance-overview', icon: <BarChart3 size={17} /> },
    { label: 'Invoices', page: 'finance-invoices', icon: <Receipt size={17} /> },
    { label: 'Bills', page: 'finance-bills', icon: <FileText size={17} /> },
    { label: 'Banking', page: 'finance-banking', icon: <Landmark size={17} /> },
    { label: 'Chart of Accounts', page: 'finance-accounts', icon: <BookOpen size={17} /> },
    { label: 'Reports', page: 'finance-reports', icon: <Download size={17} /> },
    { label: 'Students', page: 'finance-customers', icon: <Users size={17} /> },
    { label: 'Vendors', page: 'finance-vendors', icon: <Building2 size={17} /> },
    { label: 'Journal', page: 'finance-journal', icon: <Calculator size={17} /> },
  ],
  hr_manager: [
    { label: 'HR Dashboard', page: 'dashboard', icon: <LayoutDashboard size={17} /> },
    { label: 'Staff', page: 'hr-employees', icon: <Users size={17} /> },
    { label: 'Leave', page: 'hr-leave', icon: <Calendar size={17} /> },
    { label: 'Payroll', page: 'hr-payroll', icon: <Wallet size={17} /> },
    { label: 'Attendance', page: 'hr-attendance', icon: <Clock size={17} /> },
    { label: 'Expenses', page: 'hr-expenses', icon: <Receipt size={17} /> },
    { label: 'Documents', page: 'hr-documents', icon: <FileText size={17} /> },
    { label: 'HR Reports', page: 'hr-reports', icon: <BarChart3 size={17} /> },
  ],
  qa_officer: [
    { label: 'Command Center', page: 'qa-overview', icon: <BarChart3 size={17} /> },
    { label: 'Quality Plans', page: 'qa-quality-plans', icon: <ClipboardList size={17} /> },
    { label: 'Training', page: 'qa-training', icon: <GraduationCap size={17} /> },
    { label: 'Audit Logs', page: 'qa-audits', icon: <FileText size={17} /> },
    { label: 'Assessments', page: 'qa-assessments', icon: <FileCheck size={17} /> },
    { label: 'Self-Audits', page: 'qa-self-audits', icon: <Shield size={17} /> },
    { label: 'Corrective Actions', page: 'qa-corrective-actions', icon: <AlertTriangle size={17} /> },
    { label: 'Reports', page: 'qa-reports', icon: <Download size={17} /> },
  ],
}

interface Props {
  user: User
  onLogout: () => void
  children: (page: Page) => React.ReactNode
}

export default function AdminShell({ user, onLogout, children }: Props) {
  const [activePage, setActivePage] = useState<Page>('dashboard')
  const [sidebarOpen] = useState(true)
  const [notifOpen, setNotifOpen] = useState(false)
  const [userMenuOpen, setUserMenuOpen] = useState(false)
  const [mobileNavOpen, setMobileNavOpen] = useState(false)

  const navItems = NAV_BY_ROLE[user.role] ?? NAV_BY_ROLE.super_admin
  const role = ROLES[user.role]

  const notifications = [
    { text: 'APP-2402 requires your review', time: '5 min ago', unread: true },
    { text: 'New application: APP-2410 received', time: '1 hour ago', unread: true },
    { text: 'Finance report for June is ready', time: '3 hours ago', unread: false },
  ]

  return (
    <div className="flex h-screen bg-gray-50 overflow-hidden">
      {/* Mobile backdrop */}
      {mobileNavOpen && (
        <div className="fixed inset-0 bg-black/30 z-30 lg:hidden" onClick={() => setMobileNavOpen(false)} />
      )}
      {/* ── SIDEBAR ── */}
      <aside className={`${sidebarOpen ? 'w-60' : 'w-16'} flex-shrink-0 bg-white border-r border-gray-100 flex flex-col transition-all duration-200 z-40 fixed inset-y-0 left-0 transform ${mobileNavOpen ? 'translate-x-0' : '-translate-x-full'} lg:relative lg:translate-x-0`}>
        {/* Logo */}
        <div className="flex items-center gap-3 px-4 py-4 border-b border-gray-100">
          <img src={logoImg} alt="TICH" className="h-9 w-9 object-contain flex-shrink-0" />
          {sidebarOpen && (
            <div className="overflow-hidden">
              <p className="text-sm font-800 text-green-800 leading-tight" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>TICH ERP</p>
              <p className="text-[10px] text-gray-400 leading-tight">Admin Portal</p>
            </div>
          )}
        </div>

        {/* Role badge */}
        {sidebarOpen && (
          <div className="mx-3 mt-3 px-3 py-2 rounded-lg" style={{ background: role.color + '14' }}>
            <p className="text-[11px] font-600 text-gray-500">Signed in as</p>
            <p className="text-xs font-700 mt-0.5" style={{ color: role.color, fontWeight: 700 }}>{role.label}</p>
          </div>
        )}

        {/* Nav */}
        <nav className="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
          {navItems.map(({ label, page, icon }) => (
            <button key={page + label} onClick={() => setActivePage(page)}
              className={`sidebar-nav-item w-full ${activePage === page ? 'active' : ''} ${!sidebarOpen ? 'justify-center px-2' : ''}`}
              title={!sidebarOpen ? label : ''}>
              {icon}
              {sidebarOpen && <span>{label}</span>}
            </button>
          ))}
        </nav>

        {/* Logout */}
        <div className="p-2 border-t border-gray-100">
          <button onClick={onLogout}
            className={`sidebar-nav-item w-full text-red-500 hover:bg-red-50 hover:text-red-600 ${!sidebarOpen ? 'justify-center px-2' : ''}`}>
            <LogOut size={17} />
            {sidebarOpen && <span>Logout</span>}
          </button>
        </div>
      </aside>

      {/* ── MAIN AREA ── */}
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
        {/* Top bar */}
        <header className="h-14 bg-white border-b border-gray-100 flex items-center justify-between px-5 flex-shrink-0">
          <div className="flex items-center gap-3">
            <button onClick={() => setMobileNavOpen(true)} className="text-gray-400 hover:text-gray-700 transition-colors lg:hidden">
              <Menu size={20} />
            </button>
            <div>
              <p className="text-sm font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>
                {navItems.find(n => n.page === activePage)?.label ?? 'Dashboard'}
              </p>
              <p className="text-[11px] text-gray-400">TICH ERP · {new Date().toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' })}</p>
            </div>
          </div>

          <div className="flex items-center gap-2">
            {/* Notifications */}
            <div className="relative">
              <button onClick={() => { setNotifOpen(!notifOpen); setUserMenuOpen(false) }}
                className="relative w-9 h-9 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-500 transition-colors">
                <Bell size={18} />
                <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full" />
              </button>
              {notifOpen && (
                <div className="absolute right-0 top-full mt-1 w-72 bg-white border border-gray-100 rounded-xl shadow-lg z-50 overflow-hidden">
                  <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <p className="text-sm font-600">Notifications</p>
                    <button onClick={() => setNotifOpen(false)}><X size={14} className="text-gray-400" /></button>
                  </div>
                  {notifications.map((n, i) => (
                    <div key={i} className={`px-4 py-3 border-b border-gray-50 last:border-0 ${n.unread ? 'bg-green-50/50' : ''}`}>
                      <p className="text-xs text-gray-700">{n.text}</p>
                      <p className="text-[10px] text-gray-400 mt-0.5">{n.time}</p>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* User menu */}
            <div className="relative">
              <button onClick={() => { setUserMenuOpen(!userMenuOpen); setNotifOpen(false) }}
                className="flex items-center gap-2 pl-2 pr-1 py-1 rounded-lg hover:bg-gray-100 transition-colors">
                <div className="w-8 h-8 rounded-full flex items-center justify-center text-xs font-700 text-white flex-shrink-0"
                  style={{ background: role.color, fontWeight: 700 }}>
                  {user.avatar}
                </div>
                {sidebarOpen && (
                  <>
                    <div className="text-left hidden sm:block">
                      <p className="text-xs font-600 text-gray-800">{user.name}</p>
                      <p className="text-[10px] text-gray-400">{role.label}</p>
                    </div>
                    <ChevronDown size={14} className="text-gray-400" />
                  </>
                )}
              </button>
              {userMenuOpen && (
                <div className="absolute right-0 top-full mt-1 w-52 bg-white border border-gray-100 rounded-xl shadow-lg z-50 overflow-hidden">
                  <div className="px-4 py-3 border-b border-gray-100">
                    <p className="text-xs font-600 text-gray-800">{user.name}</p>
                    <p className="text-[10px] text-gray-500">{user.email}</p>
                  </div>
                  <div className="py-1">
                    <button className="w-full px-4 py-2 text-xs text-left hover:bg-gray-50 text-gray-700">Profile Settings</button>
                    <button className="w-full px-4 py-2 text-xs text-left hover:bg-gray-50 text-gray-700">Change Password</button>
                    <button onClick={onLogout} className="w-full px-4 py-2 text-xs text-left hover:bg-red-50 text-red-600">Sign Out</button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </header>

        {/* Content */}
        <main className="flex-1 overflow-y-auto p-5">
          {children(activePage)}
        </main>
      </div>
    </div>
  )
}
