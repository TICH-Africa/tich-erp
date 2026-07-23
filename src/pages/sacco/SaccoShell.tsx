import { useState } from 'react'
import {
  SACCO_MEMBERS, SACCO_LOANS, SACCO_CONTRIBUTIONS,
  type SaccoMember, type SaccoLoan, type SaccoContribution
} from '@/data/mock'
import {
  Search, Plus, ChevronRight, CheckCircle,
  Clock, BarChart3, DollarSign,
  Users, TrendingUp, Wallet,
  Bell, ChevronDown, Menu
} from 'lucide-react'

interface Props {
  user: { name: string; email: string; avatar: string; role?: string }
  onLogout: () => void
}

type SaccoSubPage = 'overview' | 'members' | 'savings' | 'loans'

export default function SaccoShell({ user, onLogout }: Props) {
  const [activeSubPage, setActiveSubPage] = useState<SaccoSubPage>('overview')
  const [notifOpen, setNotifOpen] = useState(false)
  const [userMenuOpen, setUserMenuOpen] = useState(false)
  const [mobileNavOpen, setMobileNavOpen] = useState(false)

  const subNavItems: { label: string; page: SaccoSubPage; icon: React.ReactNode }[] = [
    { label: 'Overview', page: 'overview', icon: <BarChart3 size={15} /> },
    { label: 'Members', page: 'members', icon: <Users size={15} /> },
    { label: 'Savings', page: 'savings', icon: <Wallet size={15} /> },
    { label: 'Loans', page: 'loans', icon: <DollarSign size={15} /> },
  ]

  return (
    <div className="flex h-screen bg-gray-50 overflow-hidden">
      {/* Mobile backdrop */}
      {mobileNavOpen && (
        <div className="fixed inset-0 bg-black/30 z-30 lg:hidden" onClick={() => setMobileNavOpen(false)} />
      )}
      <aside className={`w-60 flex-shrink-0 bg-white border-r border-gray-100 flex flex-col fixed inset-y-0 left-0 z-40 transform transition-transform duration-200 ${mobileNavOpen ? 'translate-x-0' : '-translate-x-full'} lg:relative lg:translate-x-0`}>
        <div className="flex items-center gap-3 px-4 py-4 border-b border-gray-100">
          <div className="w-8 h-8 rounded-full bg-amber-700 flex items-center justify-center text-white font-bold text-xs">S</div>
          <div>
            <p className="text-sm font-extrabold text-amber-800 leading-tight">SACCO</p>
            <p className="text-[10px] text-gray-400 leading-tight">TICH Portal</p>
          </div>
        </div>
        <div className="mx-3 mt-3 px-3 py-2 rounded-lg bg-amber-50">
          <p className="text-[11px] font-600 text-gray-500">Signed in as</p>
          <p className="text-xs font-700 mt-0.5 text-amber-700">{user.name}</p>
        </div>
        <nav className="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
          {subNavItems.map(({ label, page, icon }) => (
            <button key={page} onClick={() => setActiveSubPage(page)}
              className={`w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${activeSubPage === page ? 'bg-amber-100 text-amber-800 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'}`}>
              {icon}
              <span>{label}</span>
            </button>
          ))}
        </nav>
        <div className="p-2 border-t border-gray-100">
          <button onClick={onLogout} className="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-red-500 hover:bg-red-50 hover:text-red-600 transition-colors">
            Logout
          </button>
        </div>
      </aside>

      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
        <header className="h-14 bg-white border-b border-gray-100 flex items-center justify-between px-5 flex-shrink-0">
          <div className="flex items-center gap-3">
            <button onClick={() => setMobileNavOpen(true)} className="text-gray-400 hover:text-gray-700 transition-colors lg:hidden">
              <Menu size={20} />
            </button>
            <div>
              <p className="text-sm font-bold text-gray-800">{subNavItems.find(n => n.page === activeSubPage)?.label ?? 'Dashboard'}</p>
              <p className="text-xs text-gray-400">SACCO · {new Date().toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' })}</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <div className="relative">
              <button onClick={() => { setNotifOpen(!notifOpen); setUserMenuOpen(false) }} className="relative w-9 h-9 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-500 transition-colors">
                <Bell size={18} />
                <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full" />
              </button>
              {notifOpen && (
                <div className="absolute right-0 top-full mt-1 w-72 bg-white border border-gray-100 rounded-xl shadow-lg z-50 overflow-hidden">
                  <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <p className="text-sm font-600">Notifications</p>
                    <button onClick={() => setNotifOpen(false)}><span className="text-gray-400 text-xs">✕</span></button>
                  </div>
                  <div className="px-4 py-3 border-b border-gray-50">
                    <p className="text-xs text-gray-700">Loan SAC-LOAN-003 is pending</p>
                    <p className="text-[10px] text-gray-400 mt-0.5">1 hour ago</p>
                  </div>
                  <div className="px-4 py-3 border-b border-gray-50">
                    <p className="text-xs text-gray-700">July contribution reminder</p>
                    <p className="text-[10px] text-gray-400 mt-0.5">3 hours ago</p>
                  </div>
                </div>
              )}
            </div>
            <div className="relative">
              <button onClick={() => { setUserMenuOpen(!userMenuOpen); setNotifOpen(false) }} className="flex items-center gap-2 pl-2 pr-1 py-1 rounded-lg hover:bg-gray-100 transition-colors">
                <div className="w-8 h-8 rounded-full flex items-center justify-center text-xs font-700 text-white flex-shrink-0" style={{ background: '#d97706', fontWeight: 700 }}>
                  {user.avatar}
                </div>
                <span className="text-xs font-600 text-gray-800 hidden sm:block">{user.name}</span>
                <ChevronDown size={14} className="text-gray-400" />
              </button>
              {userMenuOpen && (
                <div className="absolute right-0 top-full mt-1 w-52 bg-white border border-gray-100 rounded-xl shadow-lg z-50 overflow-hidden">
                  <div className="px-4 py-3 border-b border-gray-100">
                    <p className="text-xs font-600 text-gray-800">{user.name}</p>
                    <p className="text-[10px] text-gray-500">{user.email}</p>
                  </div>
                  <div className="py-1">
                    <button className="w-full px-4 py-2 text-xs text-left hover:bg-gray-50 text-gray-700">My Profile</button>
                    <button onClick={onLogout} className="w-full px-4 py-2 text-xs text-left hover:bg-red-50 text-red-600">Sign Out</button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </header>
        <main className="flex-1 overflow-y-auto p-5">
          {activeSubPage === 'overview' && <SaccoOverview onNavigate={setActiveSubPage} />}
          {activeSubPage === 'members' && <MembersPage members={SACCO_MEMBERS} />}
          {activeSubPage === 'savings' && <SavingsPage contributions={SACCO_CONTRIBUTIONS} />}
          {activeSubPage === 'loans' && <LoansPage loans={SACCO_LOANS} />}
        </main>
      </div>
    </div>
  )
}

function StatCard({ label, value, sub, trend, color = '#15803d', icon }: {
  label: string; value: string; sub?: string; trend?: 'up' | 'down'; color?: string; icon: React.ReactNode
}) {
  return (
    <div className="bg-white border border-gray-100 rounded-xl p-4">
      <div className="flex items-start justify-between">
        <div>
          <p className="text-xs text-gray-500 font-medium">{label}</p>
          <p className="text-2xl font-extrabold mt-1 text-gray-900">{value}</p>
          {sub && <p className="text-xs mt-1 text-gray-400 flex items-center gap-1">
            {trend === 'up' && <span className="text-green-500">↑</span>}
            {sub}
          </p>}
        </div>
        <div className="w-10 h-10 rounded-xl flex items-center justify-center" style={{ background: color + '18' }}>
          <span style={{ color }}>{icon}</span>
        </div>
      </div>
    </div>
  )
}

function SaccoOverview({ onNavigate }: { onNavigate: (page: SaccoSubPage) => void }) {
  const totalMembers = SACCO_MEMBERS.length
  const totalSavings = SACCO_MEMBERS.reduce((sum, m) => sum + m.totalSavings, 0)
  const activeLoans = SACCO_LOANS.filter(l => l.status === 'disbursed' || l.status === 'pending').length
  const totalDisbursed = SACCO_LOANS.filter(l => l.status === 'disbursed').reduce((sum, l) => sum + l.amount, 0)

  return (
    <div className="space-y-5">
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <StatCard label="Total Members" value={String(totalMembers)} sub="Active members" icon={<Users size={22} />} />
        <StatCard label="Total Savings" value={`KES ${(totalSavings / 1000000).toFixed(1)}M`} sub="All members" icon={<Wallet size={22} />} color="#15803d" />
        <StatCard label="Active Loans" value={String(activeLoans)} sub="Disbursed/pending" icon={<TrendingUp size={22} />} color="#d97706" />
        <StatCard label="Total Disbursed" value={`KES ${(totalDisbursed / 1000000).toFixed(1)}M`} sub="Loan portfolio" icon={<DollarSign size={22} />} color="#1d4ed8" />
      </div>

      <div className="grid md:grid-cols-2 gap-5">
        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <h3 className="font-bold text-gray-800 mb-4">Member Savings Distribution</h3>
          <div className="space-y-3">
            {SACCO_MEMBERS.map(m => (
              <div key={m.id} className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                  <p className="text-xs font-semibold text-gray-800">{m.name}</p>
                  <p className="text-[11px] text-gray-400">{m.employeeId}</p>
                </div>
                <div className="text-right">
                  <p className="text-xs font-semibold text-gray-700">KES {m.totalSavings.toLocaleString()}</p>
                  <p className="text-[10px] text-gray-400">Monthly: KES {m.monthlyContribution.toLocaleString()}</p>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <h3 className="font-bold text-gray-800 mb-4">Recent Contributions</h3>
          <div className="space-y-2">
            {SACCO_CONTRIBUTIONS.slice(0, 5).map(c => (
              <div key={c.id} className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                  <p className="text-xs font-semibold text-gray-800">{c.memberName}</p>
                  <p className="text-[11px] text-gray-400">{c.description}</p>
                </div>
                <div className="text-right">
                  <p className="text-xs font-semibold text-green-700">KES {c.amount.toLocaleString()}</p>
                  <p className="text-[10px] text-gray-400">{c.date}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">Active Loans</h3>
          <button onClick={() => onNavigate('loans')} className="text-xs text-amber-700 font-semibold flex items-center gap-1 hover:gap-2 transition-all">View All <ChevronRight size={14} /></button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Loan ID', 'Member', 'Amount', 'Purpose', 'Status', 'Repayment Due', 'Paid'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {SACCO_LOANS.filter(l => l.status === 'disbursed' || l.status === 'pending').slice(0, 5).map(loan => (
                <tr key={loan.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{loan.id}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-800">{loan.memberName}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-700">KES {loan.amount.toLocaleString()}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{loan.purpose}</td>
                  <td className="py-3 px-4"><span className={`badge badge-${loan.status === 'disbursed' ? 'approved' : loan.status === 'pending' ? 'pending' : 'review'}`}>{loan.status}</span></td>
                  <td className="py-3 px-4 text-xs text-gray-600">{loan.repaymentDue}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">KES {loan.amountPaid.toLocaleString()}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function MembersPage({ members }: { members: SaccoMember[] }) {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const filtered = members.filter(m => {
    const matchesSearch = m.name.toLowerCase().includes(search.toLowerCase()) || m.employeeId.toLowerCase().includes(search.toLowerCase()) || m.email.toLowerCase().includes(search.toLowerCase())
    const matchesStatus = statusFilter === '' || m.status === statusFilter
    return matchesSearch && matchesStatus
  })
  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">SACCO Members</h3>
          <button className="bg-amber-700 text-white flex items-center gap-2 px-4 py-2 rounded-lg text-sm hover:bg-amber-800"><Plus size={16} /> Add Member</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search members..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Member ID', 'Name', 'Employee ID', 'Email', 'Phone', 'Join Date', 'Total Savings', 'Status'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(m => (
                <tr key={m.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{m.id}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-800">{m.name}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{m.employeeId}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{m.email}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{m.phone}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{m.joinDate}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-700">KES {m.totalSavings.toLocaleString()}</td>
                  <td className="py-3 px-4"><span className={`badge badge-${m.status === 'active' ? 'approved' : m.status === 'inactive' ? 'pending' : 'rejected'}`}>{m.status}</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function SavingsPage({ contributions }: { contributions: SaccoContribution[] }) {
  const [search, setSearch] = useState('')
  const [typeFilter, setTypeFilter] = useState('')
  const filtered = contributions.filter(c => {
    const matchesSearch = c.memberName.toLowerCase().includes(search.toLowerCase()) || c.id.toLowerCase().includes(search.toLowerCase())
    const matchesType = typeFilter === '' || c.type === typeFilter
    return matchesSearch && matchesType
  })
  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">Savings & Contributions</h3>
          <button className="bg-amber-700 text-white flex items-center gap-2 px-4 py-2 rounded-lg text-sm hover:bg-amber-800"><Plus size={16} /> Record Contribution</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search contributions..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-100" />
            </div>
            <select value={typeFilter} onChange={e => setTypeFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
              <option value="">All Types</option>
              <option value="monthly">Monthly</option>
              <option value="deposit">Deposit</option>
              <option value="withdrawal">Withdrawal</option>
              <option value="dividend">Dividend</option>
            </select>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['ID', 'Member', 'Type', 'Amount', 'Date', 'Description'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(c => (
                <tr key={c.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{c.id}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-800">{c.memberName}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{c.type}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-700">KES {c.amount.toLocaleString()}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{c.date}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{c.description}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function LoansPage({ loans }: { loans: SaccoLoan[] }) {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const filtered = loans.filter(l => {
    const matchesSearch = l.memberName.toLowerCase().includes(search.toLowerCase()) || l.id.toLowerCase().includes(search.toLowerCase())
    const matchesStatus = statusFilter === '' || l.status === statusFilter
    return matchesSearch && matchesStatus
  })
  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <StatCard label="Pending" value={String(loans.filter(l => l.status === 'pending').length)} sub="Awaiting approval" icon={<Clock size={22} />} color="#d97706" />
        <StatCard label="Approved" value={String(loans.filter(l => l.status === 'approved').length)} sub="Ready for disbursement" icon={<CheckCircle size={22} />} color="#15803d" />
        <StatCard label="Disbursed" value={String(loans.filter(l => l.status === 'disbursed').length)} sub="Active loans" icon={<DollarSign size={22} />} color="#1d4ed8" />
        <StatCard label="Repaid" value={String(loans.filter(l => l.status === 'repaid').length)} sub="Completed" icon={<TrendingUp size={22} />} color="#0d9488" />
      </div>

      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">Loan Management</h3>
          <button className="bg-amber-700 text-white flex items-center gap-2 px-4 py-2 rounded-lg text-sm hover:bg-amber-800"><Plus size={16} /> New Loan Application</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search loans..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
              <option value="">All Status</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="disbursed">Disbursed</option>
              <option value="repaid">Repaid</option>
              <option value="defaulted">Defaulted</option>
            </select>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Loan ID', 'Member', 'Amount', 'Purpose', 'Status', 'Application Date', 'Repayment Due', 'Amount Paid'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(loan => (
                <tr key={loan.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{loan.id}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-800">{loan.memberName}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-700">KES {loan.amount.toLocaleString()}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{loan.purpose}</td>
                  <td className="py-3 px-4"><span className={`badge badge-${loan.status === 'disbursed' ? 'approved' : loan.status === 'pending' ? 'pending' : loan.status === 'repaid' ? 'approved' : 'review'}`}>{loan.status}</span></td>
                  <td className="py-3 px-4 text-xs text-gray-600">{loan.applicationDate}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{loan.repaymentDue}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">KES {loan.amountPaid.toLocaleString()}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}
