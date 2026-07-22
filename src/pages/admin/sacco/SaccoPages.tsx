import { useState } from 'react'
import {
  SACCO_MEMBERS, SACCO_LOANS, SACCO_CONTRIBUTIONS,
  type SaccoMember, type SaccoLoan, type SaccoContribution
} from '@/data/mock'
import {
  Search, Plus, ChevronRight, CheckCircle,
  Clock, BarChart3,
  DollarSign, Users, TrendingUp, Wallet
} from 'lucide-react'

type SaccoSubPage = 'overview' | 'members' | 'savings' | 'loans'

interface Props {
  initialSubPage?: SaccoSubPage
}

export default function SaccoPages({ initialSubPage = 'overview' }: Props) {
  const [activeSubPage, setActiveSubPage] = useState<SaccoSubPage>(initialSubPage)

  const subNavItems: { label: string; page: SaccoSubPage; icon: React.ReactNode }[] = [
    { label: 'Overview', page: 'overview', icon: <BarChart3 size={15} /> },
    { label: 'Members', page: 'members', icon: <Users size={15} /> },
    { label: 'Savings', page: 'savings', icon: <Wallet size={15} /> },
    { label: 'Loans', page: 'loans', icon: <DollarSign size={15} /> },
  ]

  return (
    <div className="space-y-5">
      <div className="bg-white border border-gray-100 rounded-xl p-1.5 flex gap-1 overflow-x-auto">
        {subNavItems.map(({ label, page, icon }) => (
          <button key={page} onClick={() => setActiveSubPage(page)}
            className={`flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium whitespace-nowrap transition-colors ${activeSubPage === page ? 'bg-green-50 text-green-700' : 'text-gray-600 hover:bg-gray-50'}`}>
            {icon}
            <span className="hidden sm:inline">{label}</span>
          </button>
        ))}
      </div>

      {activeSubPage === 'overview' && <SaccoOverview onNavigate={setActiveSubPage} />}
      {activeSubPage === 'members' && <MembersPage members={SACCO_MEMBERS} />}
      {activeSubPage === 'savings' && <SavingsPage contributions={SACCO_CONTRIBUTIONS} />}
      {activeSubPage === 'loans' && <LoansPage loans={SACCO_LOANS} />}
    </div>
  )
}

export function renderSaccoPages(subPage: SaccoSubPage) {
  return <SaccoPages key={subPage} initialSubPage={subPage} />
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
          <h3 className="font-700 text-gray-800 mb-4" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Member Savings Distribution</h3>
          <div className="space-y-3">
            {SACCO_MEMBERS.map(m => (
              <div key={m.id} className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                  <p className="text-xs font-600 text-gray-800">{m.name}</p>
                  <p className="text-[11px] text-gray-400">{m.employeeId}</p>
                </div>
                <div className="text-right">
                  <p className="text-xs font-600 text-gray-700">KES {m.totalSavings.toLocaleString()}</p>
                  <p className="text-[10px] text-gray-400">Monthly: KES {m.monthlyContribution.toLocaleString()}</p>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <h3 className="font-700 text-gray-800 mb-4" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Recent Contributions</h3>
          <div className="space-y-2">
            {SACCO_CONTRIBUTIONS.slice(0, 5).map(c => (
              <div key={c.id} className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                  <p className="text-xs font-600 text-gray-800">{c.memberName}</p>
                  <p className="text-[11px] text-gray-400">{c.description}</p>
                </div>
                <div className="text-right">
                  <p className="text-xs font-600 text-green-700">KES {c.amount.toLocaleString()}</p>
                  <p className="text-[10px] text-gray-400">{c.date}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Active Loans</h3>
           <button onClick={() => onNavigate('loans')} className="text-xs text-green-700 font-semibold flex items-center gap-1 hover:gap-2 transition-all">View All <ChevronRight size={14} /></button>
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
                  <td className="py-3 px-4 text-xs font-600 text-gray-800">{loan.memberName}</td>
                  <td className="py-3 px-4 text-xs font-600 text-gray-700">KES {loan.amount.toLocaleString()}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{loan.purpose}</td>
                  <td className="py-3 px-4"><span className={`badge ${loan.status === 'disbursed' ? 'badge-approved' : loan.status === 'pending' ? 'badge-pending' : 'badge-review'}`}>{loan.status}</span></td>
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
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>SACCO Members</h3>
          <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> Add Member</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search members..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
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
                  <td className="py-3 px-4 text-xs font-600 text-gray-800">{m.name}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{m.employeeId}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{m.email}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{m.phone}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{m.joinDate}</td>
                  <td className="py-3 px-4 text-xs font-600 text-gray-700">KES {m.totalSavings.toLocaleString()}</td>
                  <td className="py-3 px-4"><span className={`badge ${m.status === 'active' ? 'badge-approved' : m.status === 'inactive' ? 'badge-pending' : 'badge-rejected'}`}>{m.status}</span></td>
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
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Savings & Contributions</h3>
          <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> Record Contribution</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search contributions..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
            </div>
            <select value={typeFilter} onChange={e => setTypeFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
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
                  <td className="py-3 px-4 text-xs font-600 text-gray-800">{c.memberName}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{c.type}</td>
                  <td className="py-3 px-4 text-xs font-600 text-gray-700">KES {c.amount.toLocaleString()}</td>
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
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Loan Management</h3>
          <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> New Loan Application</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search loans..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
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
                  <td className="py-3 px-4 text-xs font-600 text-gray-800">{loan.memberName}</td>
                  <td className="py-3 px-4 text-xs font-600 text-gray-700">KES {loan.amount.toLocaleString()}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{loan.purpose}</td>
                  <td className="py-3 px-4"><span className={`badge ${loan.status === 'disbursed' ? 'badge-approved' : loan.status === 'pending' ? 'badge-pending' : loan.status === 'repaid' ? 'bg-gray-100 text-gray-600' : 'badge-rejected'}`}>{loan.status}</span></td>
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

function StatCard({ label, value, sub, color = '#15803d', icon }: { label: string; value: string; sub?: string; color?: string; icon: React.ReactNode }) {
  return (
    <div className="bg-white border border-gray-100 rounded-xl p-4">
      <div className="flex items-start justify-between">
        <div>
          <p className="text-[11px] text-gray-500 font-medium">{label}</p>
          <p className="text-xl font-800 mt-1 text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>{value}</p>
          {sub && <p className="text-[10px] mt-0.5 text-gray-400">{sub}</p>}
        </div>
        <div className="w-9 h-9 rounded-lg flex items-center justify-center" style={{ background: color + '18', color }}>
          {icon}
        </div>
      </div>
    </div>
  )
}
