import { useState } from 'react'
import {
  APPLICATIONS, type Application
} from '@/data/mock'
import {
  ClipboardList, Search, Plus, ChevronRight, Eye, CheckCircle, XCircle,
  Clock, BarChart3, Download, Mail, Calendar,
  FileText, Star, GraduationCap, X
} from 'lucide-react'

type AdmissionsSubPage = 'overview' | 'applications' | 'reviews' | 'shortlisted' | 'offers' | 'registered'

interface Props {
  initialSubPage?: AdmissionsSubPage
}

export default function AdmissionsPages({ initialSubPage = 'overview' }: Props) {
  const [activeSubPage, setActiveSubPage] = useState<AdmissionsSubPage>(initialSubPage)
  const [apps, setApps] = useState<Application[]>(APPLICATIONS)
  const [selectedApp, setSelectedApp] = useState<Application | null>(null)

  const subNavItems: { label: string; page: AdmissionsSubPage; icon: React.ReactNode }[] = [
    { label: 'Overview', page: 'overview', icon: <BarChart3 size={15} /> },
    { label: 'Applications', page: 'applications', icon: <ClipboardList size={15} /> },
    { label: 'Pending Reviews', page: 'reviews', icon: <Clock size={15} /> },
    { label: 'Shortlisted', page: 'shortlisted', icon: <Star size={15} /> },
    { label: 'Offers', page: 'offers', icon: <Mail size={15} /> },
    { label: 'Registered', page: 'registered', icon: <GraduationCap size={15} /> },
  ]

  const updateStatus = (id: string, status: Application['status'], reviewedBy?: string, notes?: string) => {
    setApps(prev => prev.map(a => a.id === id ? { ...a, status, reviewedBy, notes } : a))
    setSelectedApp(null)
  }

  const shortlist = (id: string) => {
    setApps(prev => prev.map(a => a.id === id ? { ...a, status: 'under_review' as const, notes: 'Shortlisted for interview' } : a))
  }

  const confirmPayment = (id: string, amount: number) => {
    setApps(prev => prev.map(a => {
      if (a.id !== id) return a
      const newAmountPaid = a.amountPaid + amount
      const paymentStatus = newAmountPaid >= a.registrationFee ? 'paid' : 'partial'
      return { ...a, amountPaid: newAmountPaid, paymentStatus }
    }))
  }

  const sendOfferLetter = (id: string) => {
    setApps(prev => prev.map(a => {
      if (a.id !== id || a.paymentStatus !== 'paid') return a
      return { ...a, offerSent: true, offerSentDate: new Date().toISOString().split('T')[0], status: 'offer_sent' as const }
    }))
  }

  const registerStudent = (id: string) => {
    setApps(prev => prev.map(a => {
      if (a.id !== id || a.status !== 'offer_sent') return a
      return { ...a, status: 'registered' as const, registeredDate: new Date().toISOString().split('T')[0] }
    }))
  }

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

      {activeSubPage === 'overview' && <AdmissionsOverview apps={apps} onNavigate={setActiveSubPage} />}
      {activeSubPage === 'applications' && <ApplicationsPage apps={apps} onSelect={setSelectedApp} />}
      {activeSubPage === 'reviews' && <PendingReviews apps={apps} onSelect={setSelectedApp} onUpdate={updateStatus} />}
      {activeSubPage === 'shortlisted' && <ShortlistedPage apps={apps} onSelect={setSelectedApp} />}
      {activeSubPage === 'offers' && <OffersPage apps={apps} onSendOffer={sendOfferLetter} />}
      {activeSubPage === 'registered' && <RegisteredPage apps={apps} onRegister={registerStudent} />}

      {selectedApp && (
        <ApplicationModal app={selectedApp} onClose={() => setSelectedApp(null)} onUpdate={updateStatus} onShortlist={shortlist} onConfirmPayment={confirmPayment} onSendOffer={sendOfferLetter} onRegister={registerStudent} />
      )}
    </div>
  )
}

export function renderAdmissionsPages(subPage: AdmissionsSubPage) {
  return <AdmissionsPages key={subPage} initialSubPage={subPage} />
}

function AdmissionsOverview({ apps, onNavigate }: { apps: Application[]; onNavigate: (page: AdmissionsSubPage) => void }) {
  const total = apps.length
  const pending = apps.filter(a => a.status === 'pending').length
  const underReview = apps.filter(a => a.status === 'under_review').length
  const approved = apps.filter(a => a.status === 'approved').length
  const rejected = apps.filter(a => a.status === 'rejected').length
  const paid = apps.filter(a => a.paymentStatus === 'paid').length
  const partial = apps.filter(a => a.paymentStatus === 'partial').length
  const unpaid = apps.filter(a => a.paymentStatus === 'unpaid').length

  const byProgram = apps.reduce<Record<string, number>>((acc, a) => {
    acc[a.program] = (acc[a.program] || 0) + 1
    return acc
  }, {})

  const byNationality = apps.reduce<Record<string, number>>((acc, a) => {
    acc[a.nationality] = (acc[a.nationality] || 0) + 1
    return acc
  }, {})

  return (
    <div className="space-y-5">
      <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <StatCard label="Total" value={String(total)} sub="All applications" icon={<ClipboardList size={22} />} />
        <StatCard label="Pending" value={String(pending)} sub="Awaiting review" icon={<Clock size={22} />} color="#d97706" />
        <StatCard label="Under Review" value={String(underReview)} sub="In progress" icon={<Eye size={22} />} color="#1d4ed8" />
        <StatCard label="Approved" value={String(approved)} sub="Ready for offer" icon={<CheckCircle size={22} />} color="#15803d" />
        <StatCard label="Rejected" value={String(rejected)} sub="Not successful" icon={<XCircle size={22} />} color="#dc2626" />
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <StatCard label="Fully Paid" value={String(paid)} sub="Ready for offer" icon={<CheckCircle size={22} />} color="#15803d" />
        <StatCard label="Partial Payment" value={String(partial)} sub="Balance pending" icon={<Clock size={22} />} color="#d97706" />
        <StatCard label="Unpaid" value={String(unpaid)} sub="No payment" icon={<XCircle size={22} />} color="#dc2626" />
      </div>

      <div className="grid md:grid-cols-2 gap-5">
        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <h3 className="font-700 text-gray-800 mb-4" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Applications by Program</h3>
          <div className="space-y-2.5">
            {Object.entries(byProgram).sort((a, b) => b[1] - a[1]).map(([prog, count]) => (
              <div key={prog}>
                <div className="flex justify-between mb-1">
                  <span className="text-xs text-gray-700 truncate mr-2">{prog}</span>
                  <span className="text-xs font-600 text-green-700">{count}</span>
                </div>
                <div className="bg-gray-100 rounded-full h-1.5">
                  <div className="h-full bg-green-500 rounded-full transition-all" style={{ width: `${(count / total) * 100}%` }} />
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <h3 className="font-700 text-gray-800 mb-4" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Nationality Breakdown</h3>
          <div className="space-y-2">
            {Object.entries(byNationality).map(([nat, count]) => (
              <div key={nat} className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <span className="text-sm text-gray-700">{nat}</span>
                <div className="flex items-center gap-3">
                  <div className="bg-gray-100 rounded-full h-2 w-24">
                    <div className="h-full bg-green-500 rounded-full" style={{ width: `${(count / total) * 100}%` }} />
                  </div>
                  <span className="text-xs font-600 text-gray-600 w-6 text-right">{count}</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Recent Applications</h3>
          <button onClick={() => onNavigate('applications')} className="text-xs text-green-700 font-semibold flex items-center gap-1 hover:gap-2 transition-all">View All <ChevronRight size={14} /></button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['App ID', 'Student', 'Program', 'Applied', 'GPA', 'Status', 'Actions'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {apps.slice(0, 5).map(app => (
                <tr key={app.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{app.id}</td>
                  <td className="py-3 px-4">
                    <p className="text-xs font-600 text-gray-800">{app.studentName}</p>
                    <p className="text-[10px] text-gray-400">{app.email}</p>
                  </td>
                  <td className="py-3 px-4 text-xs text-gray-600">{app.program}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{app.appliedDate}</td>
                  <td className="py-3 px-4 text-xs font-600 text-gray-700">{app.gpa ?? '—'}</td>
                  <td className="py-3 px-4"><span className={`badge ${app.status === 'approved' ? 'badge-approved' : app.status === 'pending' ? 'badge-pending' : app.status === 'under_review' ? 'badge-review' : 'badge-rejected'}`}>{app.status}</span></td>
                  <td className="py-3 px-4"><button onClick={() => onNavigate('applications')} className="text-xs text-green-700 font-semibold">Review</button></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function ApplicationsPage({ apps, onSelect }: { apps: Application[]; onSelect: (app: Application) => void }) {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [programFilter, setProgramFilter] = useState('')
  const [nationalityFilter, setNationalityFilter] = useState('')
  const [paymentFilter, setPaymentFilter] = useState('')

  const filtered = apps.filter(a => {
    const matchesSearch = a.studentName.toLowerCase().includes(search.toLowerCase()) || a.id.toLowerCase().includes(search.toLowerCase()) || a.email.toLowerCase().includes(search.toLowerCase())
    const matchesStatus = statusFilter === '' || a.status === statusFilter
    const matchesProgram = programFilter === '' || a.program === programFilter
    const matchesNationality = nationalityFilter === '' || a.nationality === nationalityFilter
    const matchesPayment = paymentFilter === '' || a.paymentStatus === paymentFilter
    return matchesSearch && matchesStatus && matchesProgram && matchesNationality && matchesPayment
  })

  const programs = [...new Set(apps.map(a => a.program))]
  const nationalities = [...new Set(apps.map(a => a.nationality))]

  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>All Applications</h3>
          <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> New Application</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search by name, ID or email..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
              <option value="">All Status</option>
              <option value="pending">Pending</option>
              <option value="under_review">Under Review</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
            <select value={paymentFilter} onChange={e => setPaymentFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
              <option value="">All Payments</option>
              <option value="paid">Paid</option>
              <option value="partial">Partial</option>
              <option value="unpaid">Unpaid</option>
            </select>
            <select value={programFilter} onChange={e => setProgramFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
              <option value="">All Programs</option>
              {programs.map(p => <option key={p} value={p}>{p}</option>)}
            </select>
            <select value={nationalityFilter} onChange={e => setNationalityFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
              <option value="">All Nationalities</option>
              {nationalities.map(n => <option key={n} value={n}>{n}</option>)}
            </select>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['App ID', 'Student', 'Program', 'Applied', 'GPA', 'Payment', 'Nationality', 'Status', 'Actions'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(app => (
                <tr key={app.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{app.id}</td>
                  <td className="py-3 px-4">
                    <p className="text-xs font-600 text-gray-800">{app.studentName}</p>
                    <p className="text-[10px] text-gray-400">{app.email}</p>
                  </td>
                  <td className="py-3 px-4 text-xs text-gray-600">{app.program}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{app.appliedDate}</td>
                  <td className="py-3 px-4 text-xs font-600 text-gray-700">{app.gpa ?? '—'}</td>
                  <td className="py-3 px-4">
                    <span className={`badge ${app.paymentStatus === 'paid' ? 'badge-approved' : app.paymentStatus === 'partial' ? 'badge-pending' : 'badge-rejected'}`}>
                      {app.paymentStatus === 'paid' ? 'Paid' : app.paymentStatus === 'partial' ? 'Partial' : 'Unpaid'}
                    </span>
                    <p className="text-[10px] text-gray-400">KES {app.amountPaid.toLocaleString()}</p>
                  </td>
                  <td className="py-3 px-4 text-xs text-gray-600">{app.nationality}</td>
                  <td className="py-3 px-4"><span className={`badge ${app.status === 'approved' ? 'badge-approved' : app.status === 'pending' ? 'badge-pending' : app.status === 'under_review' ? 'badge-review' : 'badge-rejected'}`}>{app.status}</span></td>
                  <td className="py-3 px-4"><button onClick={() => onSelect(app)} className="text-xs text-green-700 font-semibold flex items-center gap-1 hover:gap-2 transition-all">View <ChevronRight size={14} /></button></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {filtered.length === 0 && <p className="text-center text-gray-400 text-sm py-8">No applications found.</p>}
      </div>
    </div>
  )
}

function PendingReviews({ apps, onSelect, onUpdate }: { apps: Application[]; onSelect: (app: Application) => void; onUpdate: (id: string, status: Application['status'], reviewedBy?: string, notes?: string) => void }) {
  const pending = apps.filter(a => a.status === 'pending' || a.status === 'under_review')

  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl p-5">
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Pending Reviews</h3>
          <span className="text-xs text-gray-400">{pending.length} applications awaiting review</span>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['App ID', 'Student', 'Program', 'Applied', 'GPA', 'Payment', 'Documents', 'Actions'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {pending.map(app => (
                <tr key={app.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{app.id}</td>
                  <td className="py-3 px-4">
                    <p className="text-xs font-600 text-gray-800">{app.studentName}</p>
                    <p className="text-[10px] text-gray-400">{app.email}</p>
                  </td>
                  <td className="py-3 px-4 text-xs text-gray-600">{app.program}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{app.appliedDate}</td>
                  <td className="py-3 px-4 text-xs font-600 text-gray-700">{app.gpa ?? '—'}</td>
                  <td className="py-3 px-4">
                    <span className={`badge ${app.paymentStatus === 'paid' ? 'badge-approved' : app.paymentStatus === 'partial' ? 'badge-pending' : 'badge-rejected'}`}>
                      {app.paymentStatus === 'paid' ? 'Paid' : app.paymentStatus === 'partial' ? 'Partial' : 'Unpaid'}
                    </span>
                  </td>
                  <td className="py-3 px-4 text-xs text-gray-600">{app.documents.length} docs</td>
                  <td className="py-3 px-4">
                    <div className="flex items-center gap-1.5">
                      <button onClick={() => onSelect(app)} className="text-xs border border-gray-200 text-gray-600 px-2.5 py-1 rounded-lg hover:bg-gray-50">Review</button>
                      <button onClick={() => { onUpdate(app.id, 'approved', 'Current User'); }} className="text-xs bg-green-700 text-white px-2.5 py-1 rounded-lg hover:bg-green-800">Approve</button>
                      <button onClick={() => { onUpdate(app.id, 'rejected', 'Current User', 'Does not meet requirements'); }} className="text-xs bg-red-100 text-red-600 px-2.5 py-1 rounded-lg hover:bg-red-200">Reject</button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {pending.length === 0 && <p className="text-center text-gray-400 text-sm py-8">No pending reviews.</p>}
      </div>
    </div>
  )
}

function ShortlistedPage({ apps, onSelect }: { apps: Application[]; onSelect: (app: Application) => void }) {
  const shortlisted = apps.filter(a => a.status === 'under_review' && a.notes?.includes('Shortlisted'))

  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100">
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Shortlisted Candidates</h3>
          <p className="text-xs text-gray-400 mt-0.5">{shortlisted.length} candidates shortlisted for interview</p>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['App ID', 'Student', 'Program', 'GPA', 'Interview Date', 'Status', 'Actions'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {shortlisted.map(app => (
                <tr key={app.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{app.id}</td>
                  <td className="py-3 px-4">
                    <p className="text-xs font-600 text-gray-800">{app.studentName}</p>
                    <p className="text-[10px] text-gray-400">{app.email}</p>
                  </td>
                  <td className="py-3 px-4 text-xs text-gray-600">{app.program}</td>
                  <td className="py-3 px-4 text-xs font-600 text-gray-700">{app.gpa ?? '—'}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">TBD</td>
                  <td className="py-3 px-4"><span className="badge badge-review">Shortlisted</span></td>
                  <td className="py-3 px-4">
                    <div className="flex items-center gap-1.5">
                      <button onClick={() => onSelect(app)} className="text-xs text-green-700 font-semibold">View</button>
                      <button className="text-xs border border-gray-200 text-gray-600 px-2.5 py-1 rounded-lg hover:bg-gray-50 flex items-center gap-1"><Calendar size={12} /> Schedule</button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {shortlisted.length === 0 && <p className="text-center text-gray-400 text-sm py-8">No shortlisted candidates yet.</p>}
      </div>
    </div>
  )
}

function OffersPage({ apps, onSendOffer }: { apps: Application[]; onSendOffer: (id: string) => void }) {
  const approvedAndPaid = apps.filter(a => a.status === 'approved' && a.paymentStatus === 'paid')

  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Admission Offers</h3>
          <button className="btn-outline text-xs flex items-center gap-1.5"><Download size={14} /> Export Offers</button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['App ID', 'Student', 'Program', 'GPA', 'Payment Status', 'Offer Date', 'Actions'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {approvedAndPaid.map(app => (
                <tr key={app.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{app.id}</td>
                  <td className="py-3 px-4">
                    <p className="text-xs font-600 text-gray-800">{app.studentName}</p>
                    <p className="text-[10px] text-gray-400">{app.email}</p>
                  </td>
                  <td className="py-3 px-4 text-xs text-gray-600">{app.program}</td>
                  <td className="py-3 px-4 text-xs font-600 text-gray-700">{app.gpa ?? '—'}</td>
                  <td className="py-3 px-4">
                    <span className={`badge ${app.paymentStatus === 'paid' ? 'badge-approved' : app.paymentStatus === 'partial' ? 'badge-pending' : 'badge-rejected'}`}>
                      {app.paymentStatus === 'paid' ? 'Paid' : app.paymentStatus === 'partial' ? 'Partial' : 'Unpaid'}
                    </span>
                    <p className="text-[10px] text-gray-400 mt-0.5">KES {app.amountPaid.toLocaleString()} / {app.registrationFee.toLocaleString()}</p>
                  </td>
                  <td className="py-3 px-4 text-xs text-gray-600">{app.offerSent ? app.offerSentDate : '—'}</td>
                  <td className="py-3 px-4">
                    <div className="flex items-center gap-1.5">
                      {!app.offerSent ? (
                        <button onClick={() => onSendOffer(app.id)} className="text-xs bg-green-700 text-white px-2.5 py-1 rounded-lg hover:bg-green-800 flex items-center gap-1"><Mail size={12} /> Send Offer</button>
                      ) : (
                        <span className="badge badge-approved">Sent</span>
                      )}
                      <button className="text-xs border border-gray-200 text-gray-600 px-2.5 py-1 rounded-lg hover:bg-gray-50"><Download size={12} /></button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {approvedAndPaid.length === 0 && <p className="text-center text-gray-400 text-sm py-8">No approved applications with confirmed payment.</p>}
      </div>
    </div>
  )
}

function RegisteredPage({ apps, onRegister }: { apps: Application[]; onRegister: (id: string) => void }) {
  const offerSent = apps.filter(a => a.status === 'offer_sent')

  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Registered Students</h3>
          <button className="btn-outline text-xs flex items-center gap-1.5"><Download size={14} /> Export List</button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Student ID', 'Name', 'Program', 'GPA', 'Payment Status', 'Offer Sent', 'Registration Date', 'Actions'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {offerSent.map(app => (
                <tr key={app.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{app.studentId}</td>
                  <td className="py-3 px-4">
                    <p className="text-xs font-600 text-gray-800">{app.studentName}</p>
                    <p className="text-[10px] text-gray-400">{app.email}</p>
                  </td>
                  <td className="py-3 px-4 text-xs text-gray-600">{app.program}</td>
                  <td className="py-3 px-4 text-xs font-600 text-gray-700">{app.gpa ?? '—'}</td>
                  <td className="py-3 px-4">
                    <span className={`badge ${app.paymentStatus === 'paid' ? 'badge-approved' : app.paymentStatus === 'partial' ? 'badge-pending' : 'badge-rejected'}`}>
                      {app.paymentStatus === 'paid' ? 'Paid' : app.paymentStatus === 'partial' ? 'Partial' : 'Unpaid'}
                    </span>
                  </td>
                  <td className="py-3 px-4 text-xs text-gray-600">{app.offerSent ? app.offerSentDate : '—'}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{app.registeredDate || '—'}</td>
                  <td className="py-3 px-4">
                    <div className="flex items-center gap-1.5">
                      {!app.registeredDate && (
                        <button onClick={() => onRegister(app.id)} className="text-xs bg-green-700 text-white px-2.5 py-1 rounded-lg hover:bg-green-800 flex items-center gap-1"><CheckCircle size={12} /> Register</button>
                      )}
                      {app.registeredDate && <span className="badge badge-approved">Registered</span>}
                      <button className="text-xs border border-gray-200 text-gray-600 px-2.5 py-1 rounded-lg hover:bg-gray-50 flex items-center gap-1"><FileText size={12} /> ID</button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {offerSent.length === 0 && <p className="text-center text-gray-400 text-sm py-8">No offer letters sent yet.</p>}
      </div>
    </div>
  )
}

function ApplicationModal({ app, onClose, onUpdate, onShortlist, onConfirmPayment, onSendOffer, onRegister }: { app: Application; onClose: () => void; onUpdate: (id: string, status: Application['status'], reviewedBy?: string, notes?: string) => void; onShortlist: (id: string) => void; onConfirmPayment: (id: string, amount: number) => void; onSendOffer: (id: string) => void; onRegister: (id: string) => void }) {
  const remaining = app.registrationFee - app.amountPaid

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" onClick={onClose}>
      <div className="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" onClick={e => e.stopPropagation()}>
        <div className="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between z-10">
          <div>
            <h3 className="font-700 text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Application Details</h3>
            <p className="text-xs text-gray-500">{app.id} · {app.program}</p>
          </div>
          <button onClick={onClose} className="p-2 hover:bg-gray-100 rounded-lg"><X size={18} /></button>
        </div>

        <div className="p-6 space-y-6">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="bg-gray-50 rounded-xl p-4">
              <p className="text-xs text-gray-500 mb-1">Student Name</p>
              <p className="text-sm font-600 text-gray-800">{app.studentName}</p>
            </div>
            <div className="bg-gray-50 rounded-xl p-4">
              <p className="text-xs text-gray-500 mb-1">Student ID</p>
              <p className="text-sm font-mono text-gray-800">{app.studentId}</p>
            </div>
            <div className="bg-gray-50 rounded-xl p-4">
              <p className="text-xs text-gray-500 mb-1">Email</p>
              <p className="text-sm text-gray-800">{app.email}</p>
            </div>
            <div className="bg-gray-50 rounded-xl p-4">
              <p className="text-xs text-gray-500 mb-1">Nationality</p>
              <p className="text-sm text-gray-800">{app.nationality}</p>
            </div>
            <div className="bg-gray-50 rounded-xl p-4">
              <p className="text-xs text-gray-500 mb-1">Program</p>
              <p className="text-sm text-gray-800">{app.program}</p>
            </div>
            <div className="bg-gray-50 rounded-xl p-4">
              <p className="text-xs text-gray-500 mb-1">Faculty</p>
              <p className="text-sm text-gray-800">{app.faculty}</p>
            </div>
            <div className="bg-gray-50 rounded-xl p-4">
              <p className="text-xs text-gray-500 mb-1">GPA</p>
              <p className="text-sm font-600 text-gray-800">{app.gpa ?? '—'}</p>
            </div>
            <div className="bg-gray-50 rounded-xl p-4">
              <p className="text-xs text-gray-500 mb-1">Applied Date</p>
              <p className="text-sm text-gray-800">{app.appliedDate}</p>
            </div>
          </div>

          <div className="bg-gray-50 rounded-xl p-5">
            <h4 className="text-sm font-700 text-gray-800 mb-3">Registration Fee Payment</h4>
            <div className="flex items-center justify-between mb-2">
              <span className="text-xs text-gray-500">Status</span>
              <span className={`badge ${app.paymentStatus === 'paid' ? 'badge-approved' : app.paymentStatus === 'partial' ? 'badge-pending' : 'badge-rejected'}`}>
                {app.paymentStatus === 'paid' ? 'Fully Paid' : app.paymentStatus === 'partial' ? 'Partially Paid' : 'Unpaid'}
              </span>
            </div>
            <div className="flex items-center justify-between mb-2">
              <span className="text-xs text-gray-500">Amount Paid</span>
              <span className="text-sm font-600 text-gray-800">KES {app.amountPaid.toLocaleString()}</span>
            </div>
            <div className="flex items-center justify-between mb-2">
              <span className="text-xs text-gray-500">Registration Fee</span>
              <span className="text-sm font-600 text-gray-800">KES {app.registrationFee.toLocaleString()}</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-xs text-gray-500">Balance</span>
              <span className={`text-sm font-600 ${remaining > 0 ? 'text-red-600' : 'text-green-600'}`}>KES {remaining.toLocaleString()}</span>
            </div>
            {remaining > 0 && (
              <div className="mt-3 pt-3 border-t border-gray-200">
                <p className="text-xs text-gray-500 mb-2">Confirm Payment Amount (KES)</p>
                <div className="flex gap-2">
                  <input
                    type="number"
                    placeholder="Enter amount"
                    className="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100"
                    id="payment-amount"
                  />
                  <button
                    onClick={() => {
                      const input = document.getElementById('payment-amount') as HTMLInputElement
                      const amount = parseInt(input.value)
                      if (amount > 0) onConfirmPayment(app.id, amount)
                    }}
                    className="btn-primary text-sm px-4 py-2"
                  >
                    Confirm Payment
                  </button>
                </div>
              </div>
            )}
          </div>

          <div>
            <h4 className="text-sm font-700 text-gray-800 mb-3">Documents</h4>
            <div className="space-y-2">
              {app.documents.map((doc, i) => (
                <div key={i} className="flex items-center gap-2 bg-gray-50 rounded-lg px-4 py-2.5">
                  <FileText size={16} className="text-gray-400" />
                  <span className="text-xs text-gray-700">{doc}</span>
                </div>
              ))}
            </div>
          </div>

          {app.notes && (
            <div>
              <h4 className="text-sm font-700 text-gray-800 mb-2">Notes</h4>
              <p className="text-xs text-gray-600 bg-gray-50 rounded-lg px-4 py-2.5">{app.notes}</p>
            </div>
          )}

          <div className="flex flex-wrap gap-2 pt-2">
            {app.status === 'approved' && app.paymentStatus === 'paid' && !app.offerSent && (
              <button onClick={() => onSendOffer(app.id)} className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Mail size={16} /> Send Offer Letter</button>
            )}
            {app.status === 'offer_sent' && !app.registeredDate && (
              <button onClick={() => onRegister(app.id)} className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><CheckCircle size={16} /> Register Student</button>
            )}
            {app.status !== 'approved' && app.status !== 'rejected' && (
              <button onClick={() => onShortlist(app.id)} className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Star size={16} /> Shortlist</button>
            )}
            {app.status !== 'approved' && app.status !== 'rejected' && (
              <button onClick={() => onUpdate(app.id, 'approved', 'Current User')} className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><CheckCircle size={16} /> Approve</button>
            )}
            {app.status !== 'rejected' && (
              <button onClick={() => onUpdate(app.id, 'rejected', 'Current User', 'Does not meet requirements')} className="bg-red-100 text-red-600 flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-red-200 transition-colors"><XCircle size={16} /> Reject</button>
            )}
            <button onClick={onClose} className="btn-outline px-4 py-2 text-sm">Close</button>
          </div>
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
