import { useState } from 'react'
import {
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip,
  ResponsiveContainer, AreaChart, Area, Legend
} from 'recharts'
import {
  APPLICATIONS, DEPARTMENTS, PROGRAMS, REVENUE_DATA,
  DEPT_PERFORMANCE, STAFF, QA_METRICS, type Application, type Role
} from '@/data/mock'
import { renderHRPages } from '../hr/HRPages'
import { renderFinancePages } from '../finance/FinancePages'
import { renderAcademicsPages } from '../academics/AcademicsPages'
import { renderAdmissionsPages } from '../admissions/AdmissionsPages'
import { renderQAPages } from '../qa/QAPages'
import {
  TrendingUp, TrendingDown, Users, GraduationCap, DollarSign, FileCheck,
  AlertTriangle, CheckCircle, XCircle, Clock, Eye, Filter, Download,
  Star, Award, BarChart3, Briefcase, UserCheck, Search, BookOpen, Plus, FileText, Calendar
} from 'lucide-react'

// ── SHARED COMPONENTS ─────────────────────────────────────────────────────────

function StatCard({ label, value, sub, trend, color = '#15803d', icon }: {
  label: string; value: string; sub?: string; trend?: 'up' | 'down'; color?: string; icon: React.ReactNode
}) {
  return (
    <div className="stat-card">
      <div className="flex items-start justify-between">
        <div>
          <p className="text-xs text-gray-500 font-medium">{label}</p>
          <p className="text-2xl font-800 mt-1 text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>{value}</p>
          {sub && <p className="text-xs mt-1 text-gray-400 flex items-center gap-1">
            {trend === 'up' && <TrendingUp size={11} className="text-green-500" />}
            {trend === 'down' && <TrendingDown size={11} className="text-red-500" />}
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

function SectionHeader({ title, subtitle, action }: { title: string; subtitle?: string; action?: React.ReactNode }) {
  return (
    <div className="flex items-end justify-between mb-4">
      <div>
        <h2 className="text-base font-700 text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>{title}</h2>
        {subtitle && <p className="text-xs text-gray-400 mt-0.5">{subtitle}</p>}
      </div>
      {action}
    </div>
  )
}

// ── APPROVALS PANEL (shared by Academic Registrar, Dean, CEO) ─────────────────

function ApprovalsPanel({ role }: { role: Role }) {
  const isCEO = role === 'ceo'
  const [apps, setApps] = useState(APPLICATIONS)
  const [filter, setFilter] = useState<'all' | 'pending' | 'under_review' | 'approved' | 'rejected'>('all')
  const [selected, setSelected] = useState<Application | null>(null)

  const filtered = apps.filter(a => filter === 'all' || a.status === filter)

  const updateStatus = (id: string, status: 'approved' | 'rejected') => {
    setApps(prev => prev.map(a => a.id === id ? { ...a, status } : a))
    setSelected(null)
  }

  const counts = {
    all: apps.length,
    pending: apps.filter(a => a.status === 'pending').length,
    under_review: apps.filter(a => a.status === 'under_review').length,
    approved: apps.filter(a => a.status === 'approved').length,
    rejected: apps.filter(a => a.status === 'rejected').length,
  }

  return (
    <div className="space-y-5">
      {isCEO && (
        <div className="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 flex items-center gap-3">
          <AlertTriangle size={16} className="text-amber-600 flex-shrink-0" />
          <p className="text-sm text-amber-800"><strong>CEO Override Mode:</strong> You can approve or reject any application regardless of prior review status.</p>
        </div>
      )}

      {/* Summary strip */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        {[
          { key: 'pending', label: 'Pending', color: '#d97706', bg: '#fef3c7' },
          { key: 'under_review', label: 'Under Review', color: '#1d4ed8', bg: '#dbeafe' },
          { key: 'approved', label: 'Approved', color: '#15803d', bg: '#dcfce7' },
          { key: 'rejected', label: 'Rejected', color: '#dc2626', bg: '#fee2e2' },
        ].map(({ key, label, color }) => (
          <button key={key} onClick={() => setFilter(key as typeof filter)}
            className="stat-card text-left transition-all"
            style={{ borderColor: filter === key ? color : undefined, borderWidth: filter === key ? 2 : 1 }}>
            <p className="text-xs text-gray-500">{label}</p>
            <p className="text-xl font-800 mt-1" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800, color }}>
              {counts[key as keyof typeof counts]}
            </p>
          </button>
        ))}
      </div>

      {/* Filter tabs */}
      <div className="flex items-center gap-2 flex-wrap">
        {(['all', 'pending', 'under_review', 'approved', 'rejected'] as const).map(f => (
          <button key={f} onClick={() => setFilter(f)}
            className={`px-3 py-1.5 rounded-lg text-xs font-500 transition-colors ${filter === f ? 'bg-green-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-green-50'}`}>
            {f === 'all' ? 'All Applications' : f.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())} {filter === f && `(${filtered.length})`}
          </button>
        ))}
        <div className="ml-auto flex items-center gap-2">
          <button className="btn-outline text-xs flex items-center gap-1"><Filter size={12} /> Filter</button>
          <button className="btn-outline text-xs flex items-center gap-1"><Download size={12} /> Export</button>
        </div>
      </div>

      <div className="grid md:grid-cols-2 gap-3">
        {/* List */}
        <div className="space-y-2">
          {filtered.map(app => (
            <div key={app.id} onClick={() => setSelected(app)}
              className={`approval-card cursor-pointer ${selected?.id === app.id ? 'border-green-400 shadow-sm' : ''}`}>
              <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                  <div className="flex items-center gap-2">
                    <p className="text-xs font-600 text-gray-400" style={{ fontFamily: 'var(--font-mono)' }}>{app.id}</p>
                    <span className={`badge badge-${app.status === 'under_review' ? 'review' : app.status}`}>
                      {app.status.replace('_', ' ')}
                    </span>
                  </div>
                  <p className="text-sm font-700 text-gray-900 mt-0.5" style={{ fontWeight: 700 }}>{app.studentName}</p>
                  <p className="text-xs text-gray-500 truncate">{app.program}</p>
                  <p className="text-xs text-gray-400 mt-0.5">{app.appliedDate} · {app.nationality}</p>
                </div>
                {app.gpa && <div className="flex-shrink-0 text-right">
                  <p className="text-xs text-gray-400">GPA</p>
                  <p className="text-base font-700 text-green-700" style={{ fontWeight: 700 }}>{app.gpa}</p>
                </div>}
              </div>
              {(app.status === 'pending' || (isCEO && app.status !== 'rejected')) && (
                <div className="flex gap-2 mt-3">
                  <button onClick={(e) => { e.stopPropagation(); updateStatus(app.id, 'approved') }}
                    className="btn-primary text-xs px-3 py-1.5 flex items-center gap-1">
                    <CheckCircle size={12} /> Approve
                  </button>
                  <button onClick={(e) => { e.stopPropagation(); updateStatus(app.id, 'rejected') }}
                    className="btn-danger text-xs px-3 py-1.5 flex items-center gap-1">
                    <XCircle size={12} /> Reject
                  </button>
                  {!isCEO && <button onClick={(e) => { e.stopPropagation(); updateStatus(app.id, 'approved') }}
                    className="btn-outline text-xs px-3 py-1.5 flex items-center gap-1">
                    <Eye size={12} /> Review
                  </button>}
                </div>
              )}
            </div>
          ))}
          {filtered.length === 0 && (
            <div className="text-center py-12 text-gray-400">
              <FileCheck size={32} className="mx-auto mb-2 opacity-30" />
              <p className="text-sm">No applications in this category</p>
            </div>
          )}
        </div>

        {/* Detail panel */}
        <div className="bg-white border border-gray-100 rounded-xl p-5 h-fit sticky top-0">
          {selected ? (
            <div>
              <div className="flex items-center justify-between mb-4">
                <p className="text-xs font-600 text-gray-400" style={{ fontFamily: 'var(--font-mono)' }}>{selected.id}</p>
                <span className={`badge badge-${selected.status === 'under_review' ? 'review' : selected.status}`}>
                  {selected.status.replace('_', ' ')}
                </span>
              </div>
              <h3 className="text-lg font-700 text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>{selected.studentName}</h3>
              <p className="text-xs text-gray-400 mb-4">{selected.email}</p>
              <div className="grid grid-cols-2 gap-3 mb-4">
                {[
                  { label: 'Program', value: selected.program },
                  { label: 'Faculty', value: selected.faculty },
                  { label: 'Applied', value: selected.appliedDate },
                  { label: 'Nationality', value: selected.nationality },
                  { label: 'GPA / Grade', value: selected.gpa ? `${selected.gpa} / 4.0` : '—' },
                  { label: 'Student ID', value: selected.studentId, mono: true },
                ].map(({ label, value, mono }) => (
                  <div key={label}>
                    <p className="text-[10px] text-gray-400 font-medium">{label}</p>
                    <p className={`text-xs text-gray-800 mt-0.5 font-500 ${mono ? 'font-mono' : ''}`} style={mono ? { fontFamily: 'var(--font-mono)' } : {}}>{value}</p>
                  </div>
                ))}
              </div>
              <div className="mb-4">
                <p className="text-[10px] text-gray-400 font-medium mb-1.5">Submitted Documents</p>
                <div className="flex flex-wrap gap-1.5">
                  {selected.documents.map(doc => (
                    <span key={doc} className="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{doc}</span>
                  ))}
                </div>
              </div>
              {selected.notes && (
                <div className="bg-amber-50 border border-amber-100 rounded-lg p-3 mb-4">
                  <p className="text-xs text-amber-700"><strong>Notes:</strong> {selected.notes}</p>
                </div>
              )}
              {(selected.status === 'pending' || selected.status === 'under_review' || isCEO) && (
                <div className="flex gap-2 pt-4 border-t border-gray-100">
                  <button onClick={() => updateStatus(selected.id, 'approved')} className="btn-primary flex-1 py-2 text-sm flex items-center justify-center gap-1.5">
                    <CheckCircle size={14} /> {isCEO ? 'CEO Approve' : 'Approve'}
                  </button>
                  <button onClick={() => updateStatus(selected.id, 'rejected')} className="btn-danger flex-1 py-2 text-sm flex items-center justify-center gap-1.5">
                    <XCircle size={14} /> Reject
                  </button>
                </div>
              )}
            </div>
          ) : (
            <div className="text-center py-12 text-gray-300">
              <Eye size={32} className="mx-auto mb-2" />
              <p className="text-sm text-gray-400">Click an application to view details</p>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

// ── SUPER ADMIN DASHBOARD ─────────────────────────────────────────────────────

function SuperAdminDash() {
  const cmsStats = [
    { label: 'Published Pages', value: '24', sub: '3 updated this week', icon: <FileText size={20} />, color: '#15803d' },
    { label: 'Active Events', value: '7', sub: '2 upcoming this month', icon: <Calendar size={20} />, color: '#1d4ed8' },
    { label: 'Courses Listed', value: '18', sub: '12 active · 6 archived', icon: <BookOpen size={20} />, color: '#d97706' },
    { label: 'Media Assets', value: '86', sub: 'Hero, banners, logos', icon: <Star size={20} />, color: '#7c3aed' },
  ]

  const recentUpdates = [
    { action: 'Hero banner updated', user: 'a.osei@tich.or.ke', time: '2 hours ago', ip: '192.168.1.1' },
    { action: 'New course added: Diploma in Data Science', user: 'a.osei@tich.or.ke', time: '5 hours ago', ip: '192.168.1.1' },
    { action: 'Event published: Annual Culinary Showcase', user: 'a.osei@tich.or.ke', time: '1 day ago', ip: '192.168.1.1' },
    { action: 'News post published', user: 'a.osei@tich.or.ke', time: '2 days ago', ip: '192.168.1.1' },
  ]

  return (
    <div className="space-y-5">
      <div className="bg-gradient-to-r from-green-800 to-green-700 rounded-xl p-5 text-white">
        <p className="text-xs text-green-200 font-medium mb-1">Content Management · {new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>
        <h2 className="text-xl font-extrabold">Welcome back, Dr. Osei</h2>
        <p className="text-sm text-green-200 mt-1">You have full system access. Manage website content, courses, and events.</p>
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        {cmsStats.map(s => (
          <div key={s.label} className="stat-card">
            <p className="text-xs text-gray-500">{s.label}</p>
            <p className="text-xl font-extrabold mt-1 text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>{s.value}</p>
            <p className="text-xs mt-1 text-gray-400">{s.sub}</p>
          </div>
        ))}
      </div>

      <div className="grid md:grid-cols-2 gap-5">
        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <SectionHeader title="Recent Updates" subtitle="Latest website changes" action={
            <button className="btn-outline text-xs flex items-center gap-1"><Eye size={12} /> View All</button>
          } />
          <div className="space-y-2">
            {recentUpdates.map((log, i) => (
              <div key={i} className="flex items-start justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                  <p className="text-xs font-500 text-gray-800">{log.action}</p>
                  <p className="text-[11px] text-gray-400">{log.user}</p>
                </div>
                <div className="text-right">
                  <p className="text-[11px] text-gray-500">{log.time}</p>
                  <p className="text-[10px] text-gray-300" style={{ fontFamily: 'var(--font-mono)' }}>{log.ip}</p>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <SectionHeader title="Quick Actions" subtitle="Common CMS tasks" />
          <div className="grid grid-cols-2 gap-2">
            <button className="btn-outline text-xs py-2.5 flex items-center justify-center gap-1.5"><FileText size={14} /> New Page</button>
            <button className="btn-outline text-xs py-2.5 flex items-center justify-center gap-1.5"><BookOpen size={14} /> Add Course</button>
            <button className="btn-outline text-xs py-2.5 flex items-center justify-center gap-1.5"><Calendar size={14} /> Create Event</button>
            <button className="btn-outline text-xs py-2.5 flex items-center justify-center gap-1.5"><Star size={14} /> Update Hero</button>
          </div>
        </div>
      </div>
    </div>
  )
}

function CMSDash() {
  const pages = [
    { id: 'P001', title: 'Homepage', status: 'published', updatedBy: 'Dr. Osei', updatedAt: '2025-07-22 09:30' },
    { id: 'P002', title: 'About Us', status: 'published', updatedBy: 'Dr. Osei', updatedAt: '2025-07-21 14:20' },
    { id: 'P003', title: 'Programs', status: 'published', updatedBy: 'Dr. Osei', updatedAt: '2025-07-20 11:00' },
    { id: 'P004', title: 'Admissions', status: 'draft', updatedBy: 'Dr. Osei', updatedAt: '2025-07-19 16:45' },
    { id: 'P005', title: 'Contact', status: 'published', updatedBy: 'Dr. Osei', updatedAt: '2025-07-18 08:15' },
  ]

  return (
    <div className="space-y-5">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Website Pages</h3>
          <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> New Page</button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Page ID', 'Title', 'Status', 'Updated By', 'Updated At'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {pages.map(p => (
                <tr key={p.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{p.id}</td>
                  <td className="py-3 px-4 text-xs font-600 text-gray-800">{p.title}</td>
                  <td className="py-3 px-4"><span className={`badge ${p.status === 'published' ? 'badge-approved' : 'badge-pending'}`}>{p.status}</span></td>
                  <td className="py-3 px-4 text-xs text-gray-600">{p.updatedBy}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{p.updatedAt}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function EventsDash() {
  const events = [
    { id: 'EVT-001', title: 'Annual Culinary Showcase', date: '2025-08-15', location: 'TICH Main Campus', status: 'published' },
    { id: 'EVT-002', title: 'Open Day', date: '2025-09-05', location: 'TICH Main Campus', status: 'published' },
    { id: 'EVT-003', title: 'Scholarship Interview Week', date: '2025-09-20', location: 'Online', status: 'draft' },
    { id: 'EVT-004', title: 'Graduation Ceremony', date: '2025-12-12', location: 'TICH Main Campus', status: 'draft' },
  ]

  return (
    <div className="space-y-5">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Events</h3>
          <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> New Event</button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Event ID', 'Title', 'Date', 'Location', 'Status'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {events.map(evt => (
                <tr key={evt.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{evt.id}</td>
                  <td className="py-3 px-4 text-xs font-600 text-gray-800">{evt.title}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{evt.date}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{evt.location}</td>
                  <td className="py-3 px-4"><span className={`badge ${evt.status === 'published' ? 'badge-approved' : 'badge-pending'}`}>{evt.status}</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

// ── CEO DASHBOARD ─────────────────────────────────────────────────────────────

function CEODash() {
  const kpis = [
    { label: 'Total Enrolment', value: '1,640', change: '+8.8%', up: true },
    { label: 'Revenue YTD', value: 'KES 142M', change: '+12.3%', up: true },
    { label: 'Staff Count', value: '127', change: '+4', up: true },
    { label: 'Avg Pass Rate', value: '86.2%', change: '+1.4pp', up: true },
  ]

  return (
    <div className="space-y-5">
      <div className="bg-gradient-to-r from-green-800 to-green-700 rounded-xl p-5 text-white">
        <p className="text-xs text-green-200 font-medium mb-1">Executive Summary · Q2 2025</p>
        <h2 className="text-xl font-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>Good morning, Prof. Kariuki</h2>
        <p className="text-sm text-green-200 mt-1">TICH is on track with growth targets. 3 applications require your review.</p>
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        {kpis.map(k => (
          <div key={k.label} className="stat-card">
            <p className="text-xs text-gray-500">{k.label}</p>
            <p className="text-xl font-800 mt-1 text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>{k.value}</p>
            <p className={`text-xs mt-1 flex items-center gap-1 ${k.up ? 'text-green-600' : 'text-red-500'}`}>
              {k.up ? <TrendingUp size={11} /> : <TrendingDown size={11} />} {k.change} vs last year
            </p>
          </div>
        ))}
      </div>

      <div className="grid md:grid-cols-2 gap-5">
        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <SectionHeader title="Revenue Breakdown" subtitle="Last 7 months (KES)" />
          <ResponsiveContainer width="100%" height={200}>
             <BarChart data={REVENUE_DATA}>
               <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
               <XAxis dataKey="month" tick={{ fontSize: 11 }} />
               <YAxis tick={{ fontSize: 11 }} tickFormatter={v => `${(v/1e6).toFixed(0)}M`} />
               <Tooltip formatter={(v) => `KES ${(v as number / 1e6).toFixed(1)}M`} contentStyle={{ fontSize: 12, borderRadius: 8 }} />
               <Legend wrapperStyle={{ fontSize: 11 }} />
              <Bar dataKey="tuition" fill="#15803d" name="Tuition" radius={[3,3,0,0]} />
              <Bar dataKey="accommodation" fill="#0d9488" name="Accommodation" radius={[3,3,0,0]} />
              <Bar dataKey="other" fill="#d97706" name="Other" radius={[3,3,0,0]} />
            </BarChart>
          </ResponsiveContainer>
        </div>

        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <SectionHeader title="Department Performance" subtitle="Pass rate by faculty" />
          <div className="space-y-3 mt-2">
            {DEPT_PERFORMANCE.map(d => (
              <div key={d.dept}>
                <div className="flex justify-between mb-1">
                  <span className="text-xs font-500 text-gray-700">{d.dept}</span>
                  <span className="text-xs font-600 text-green-700">{d.passRate}%</span>
                </div>
                <div className="bg-gray-100 rounded-full h-2">
                  <div className="h-full bg-green-500 rounded-full transition-all" style={{ width: `${d.passRate}%` }} />
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="bg-white border border-gray-100 rounded-xl p-5">
        <SectionHeader title="Applications Requiring CEO Review" subtitle="Override approval available" />
        <ApprovalsPanel role="ceo" />
      </div>
    </div>
  )
}

// ── PRINCIPAL DASHBOARD ───────────────────────────────────────────────────────

// ── ACADEMIC REGISTRAR DASHBOARD ─────────────────────────────────────────────

function RegistrarDash() {
  const pending = APPLICATIONS.filter(a => a.status === 'pending').length
  const approved = APPLICATIONS.filter(a => a.status === 'approved').length
  const underReview = APPLICATIONS.filter(a => a.status === 'under_review').length

  return (
    <div className="space-y-5">
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <StatCard label="Pending Approvals" value={String(pending)} sub="Requires action" color="#d97706" icon={<Clock size={20} />} />
        <StatCard label="Under Review" value={String(underReview)} sub="In progress" color="#1d4ed8" icon={<Eye size={20} />} />
        <StatCard label="Approved" value={String(approved)} sub="This intake" icon={<CheckCircle size={20} />} />
        <StatCard label="Total Applications" value={String(APPLICATIONS.length)} sub="Sept 2025 intake" icon={<FileCheck size={20} />} color="#7c3aed" />
      </div>

      <div className="bg-white border border-gray-100 rounded-xl p-5">
        <SectionHeader title="Application Approval Queue" subtitle="September 2025 Intake · Academic Registrar Review" />
        <ApprovalsPanel role="academic_registrar" />
      </div>
    </div>
  )
}

// ── HOD DASHBOARD ─────────────────────────────────────────────────────────────

function HODDash() {
  return (
    <div className="space-y-5">
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <StatCard label="Dept Students" value="215" sub="Hospitality Mgmt" icon={<GraduationCap size={20} />} />
        <StatCard label="Dept Courses" value="8" sub="Current semester" icon={<BookOpen size={20} />} color="#1d4ed8" />
        <StatCard label="Dept Faculty" value="12" sub="3 on leave" icon={<Users size={20} />} color="#0d9488" />
        <StatCard label="Dept Pass Rate" value="87%" sub="+1pp vs last sem" trend="up" icon={<Award size={20} />} color="#d97706" />
      </div>

      <div className="grid md:grid-cols-2 gap-5">
        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <SectionHeader title="Courses This Semester" />
          {[
            { code: 'HM 201', name: 'Hotel Operations Management', students: 42, instructor: 'Dr. Ruth Wambua' },
            { code: 'HM 202', name: 'Food & Beverage Service', students: 38, instructor: 'Prof. Ibrahim Juma' },
            { code: 'HM 203', name: 'Front Office Procedures', students: 45, instructor: 'Mr. Hassan Khamis' },
            { code: 'HM 301', name: 'Strategic Hospitality Mgmt', students: 31, instructor: 'Dr. Miriam Akinyi' },
            { code: 'HM 204', name: 'Housekeeping Management', students: 39, instructor: 'Ms. Cynthia Otieno' },
          ].map(c => (
            <div key={c.code} className="flex items-center justify-between py-2.5 border-b border-gray-50 last:border-0">
              <div>
                <div className="flex items-center gap-2">
                  <span className="text-xs font-600 text-green-700" style={{ fontFamily: 'var(--font-mono)' }}>{c.code}</span>
                  <span className="text-xs text-gray-800">{c.name}</span>
                </div>
                <p className="text-[11px] text-gray-400 mt-0.5">{c.instructor}</p>
              </div>
              <span className="text-xs font-600 text-gray-600">{c.students} students</span>
            </div>
          ))}
        </div>

        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <SectionHeader title="Department Faculty" />
          <div className="space-y-2">
            {STAFF.filter(s => s.dept.includes('Hotel') || s.dept.includes('Hospitality')).map(s => (
              <div key={s.id} className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div className="flex items-center gap-2.5">
                  <div className="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-xs font-700" style={{ fontWeight: 700 }}>
                    {s.name.split(' ').map(n => n[0]).join('').slice(0, 2)}
                  </div>
                  <div>
                    <p className="text-xs font-600 text-gray-800">{s.name}</p>
                    <p className="text-[11px] text-gray-400">{s.role}</p>
                  </div>
                </div>
                <div className="text-right">
                  <span className={`badge ${s.leave ? 'badge-pending' : 'badge-approved'}`}>{s.leave ? 'On Leave' : 'Active'}</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}

// ── ADMISSIONS OFFICER DASHBOARD ──────────────────────────────────────────────

function AdmissionsDash() {
  const pipeline = [
    { stage: 'Received', count: APPLICATIONS.length, color: '#6366f1' },
    { stage: 'Under Review', count: APPLICATIONS.filter(a => a.status === 'under_review').length, color: '#1d4ed8' },
    { stage: 'Pending Decision', count: APPLICATIONS.filter(a => a.status === 'pending').length, color: '#d97706' },
    { stage: 'Approved', count: APPLICATIONS.filter(a => a.status === 'approved').length, color: '#15803d' },
    { stage: 'Rejected', count: APPLICATIONS.filter(a => a.status === 'rejected').length, color: '#dc2626' },
  ]

  return (
    <div className="space-y-5">
      <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
        {pipeline.map(p => (
          <div key={p.stage} className="stat-card">
            <p className="text-xs text-gray-500">{p.stage}</p>
            <p className="text-2xl font-800 mt-1" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800, color: p.color }}>{p.count}</p>
          </div>
        ))}
      </div>

      <div className="grid md:grid-cols-2 gap-5">
        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <SectionHeader title="Applications by Program" subtitle="September 2025 intake" />
          <div className="space-y-2.5">
            {['Diploma in Hospitality Management', 'Bachelor of Hospitality Mgmt', 'Diploma in Tourism & Travel', 'Bachelor of Tourism Management', 'Certificate in Hotel Operations'].map((prog) => {
              const count = APPLICATIONS.filter(a => a.program === prog).length
              if (!count) return null
              return (
                <div key={prog}>
                  <div className="flex justify-between mb-1">
                    <span className="text-xs text-gray-700 truncate mr-2">{prog}</span>
                    <span className="text-xs font-600 text-green-700">{count}</span>
                  </div>
                  <div className="bg-gray-100 rounded-full h-1.5">
                    <div className="h-full bg-green-500 rounded-full" style={{ width: `${(count / APPLICATIONS.length) * 100}%` }} />
                  </div>
                </div>
              )
            })}
          </div>
        </div>

        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <SectionHeader title="Nationality Breakdown" />
          {['Kenyan', 'Rwandan', 'Ugandan'].map(nat => {
            const count = APPLICATIONS.filter(a => a.nationality === nat).length
            return (
              <div key={nat} className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <span className="text-sm text-gray-700">{nat}</span>
                <div className="flex items-center gap-3">
                  <div className="bg-gray-100 rounded-full h-2 w-24">
                    <div className="h-full bg-green-500 rounded-full" style={{ width: `${(count / APPLICATIONS.length) * 100}%` }} />
                  </div>
                  <span className="text-xs font-600 text-gray-600 w-6 text-right">{count}</span>
                </div>
              </div>
            )
          })}
        </div>
      </div>

      <div className="bg-white border border-gray-100 rounded-xl p-5">
        <SectionHeader title="All Applications" subtitle="Full pipeline view · September 2025" />
        <ApprovalsPanel role="admissions_officer" />
      </div>
    </div>
  )
}

// ── FINANCE MANAGER DASHBOARD ─────────────────────────────────────────────────

function FinanceDash() {
  const budget = [
    { category: 'Staff Salaries', allocated: 68000000, spent: 61200000 },
    { category: 'Infrastructure', allocated: 15000000, spent: 9800000 },
    { category: 'Academic Resources', allocated: 8000000, spent: 6400000 },
    { category: 'Marketing', allocated: 4000000, spent: 3200000 },
    { category: 'Operations', allocated: 12000000, spent: 10800000 },
  ]

  return (
    <div className="space-y-5">
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <StatCard label="Revenue YTD" value="KES 142M" sub="+12.3% vs target" trend="up" icon={<TrendingUp size={20} />} />
        <StatCard label="Fee Collection Rate" value="87.4%" sub="+4.1pp" trend="up" icon={<DollarSign size={20} />} color="#0d9488" />
        <StatCard label="Outstanding Fees" value="KES 18.4M" sub="342 students" color="#d97706" icon={<AlertTriangle size={20} />} />
        <StatCard label="Budget Utilisation" value="73.2%" sub="Within targets" icon={<BarChart3 size={20} />} color="#1d4ed8" />
      </div>

      <div className="grid md:grid-cols-2 gap-5">
        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <SectionHeader title="Monthly Revenue" subtitle="Tuition · Accommodation · Other (KES)" />
          <ResponsiveContainer width="100%" height={200}>
             <AreaChart data={REVENUE_DATA}>
               <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
               <XAxis dataKey="month" tick={{ fontSize: 11 }} />
               <YAxis tick={{ fontSize: 11 }} tickFormatter={v => `${(v/1e6).toFixed(0)}M`} />
               <Tooltip formatter={(v) => `KES ${(v as number / 1e6).toFixed(1)}M`} contentStyle={{ fontSize: 12, borderRadius: 8 }} />
               <Area type="monotone" dataKey="tuition" stackId="1" stroke="#15803d" fill="#dcfce7" strokeWidth={2} name="Tuition" />
              <Area type="monotone" dataKey="accommodation" stackId="1" stroke="#0d9488" fill="#ccfbf1" strokeWidth={2} name="Accommodation" />
              <Area type="monotone" dataKey="other" stackId="1" stroke="#d97706" fill="#fef3c7" strokeWidth={2} name="Other" />
            </AreaChart>
          </ResponsiveContainer>
        </div>

        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <SectionHeader title="Budget vs Actual" subtitle="FY 2026 by category (KES)" />
          <div className="space-y-3">
            {budget.map(b => {
              const pct = Math.round((b.spent / b.allocated) * 100)
              return (
                <div key={b.category}>
                  <div className="flex justify-between mb-1">
                    <span className="text-xs text-gray-700">{b.category}</span>
                    <span className="text-xs font-600" style={{ color: pct > 90 ? '#dc2626' : '#15803d' }}>{pct}%</span>
                  </div>
                  <div className="bg-gray-100 rounded-full h-2">
                    <div className="h-full rounded-full transition-all" style={{ width: `${pct}%`, background: pct > 90 ? '#dc2626' : '#15803d' }} />
                  </div>
                  <div className="flex justify-between mt-0.5">
                    <span className="text-[10px] text-gray-400">Spent: KES {(b.spent/1e6).toFixed(1)}M</span>
                    <span className="text-[10px] text-gray-400">Budget: KES {(b.allocated/1e6).toFixed(0)}M</span>
                  </div>
                </div>
              )
            })}
          </div>
        </div>
      </div>
    </div>
  )
}

// ── HR MANAGER DASHBOARD ──────────────────────────────────────────────────────

function HRDash() {
  const byDept: Record<string, number> = {}
  STAFF.forEach(s => { byDept[s.dept] = (byDept[s.dept] || 0) + 1 })
  const deptData = Object.entries(byDept).map(([dept, count]) => ({ dept, count }))

  return (
    <div className="space-y-5">
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <StatCard label="Total Staff" value="127" sub="+4 this quarter" trend="up" icon={<Users size={20} />} />
        <StatCard label="Teaching Staff" value="48" sub="Full-time faculty" icon={<GraduationCap size={20} />} color="#1d4ed8" />
        <StatCard label="On Leave" value="5" sub="Current approvals" color="#d97706" icon={<Clock size={20} />} />
        <StatCard label="Open Vacancies" value="3" sub="Recruitment active" icon={<Briefcase size={20} />} color="#7c3aed" />
      </div>

      <div className="grid md:grid-cols-2 gap-5">
        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <SectionHeader title="Staff Roster" subtitle="All teaching staff" action={
            <button className="btn-outline text-xs flex items-center gap-1"><Download size={12} /> Export</button>
          } />
          <div className="space-y-1 mt-2">
            {STAFF.map(s => (
              <div key={s.id} className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div className="flex items-center gap-2.5">
                  <div className="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-xs font-700" style={{ fontWeight: 700 }}>
                    {s.name.split(' ').map(n => n[0]).join('').slice(0, 2)}
                  </div>
                  <div>
                    <p className="text-xs font-600 text-gray-800">{s.name}</p>
                    <p className="text-[11px] text-gray-400">{s.dept}</p>
                  </div>
                </div>
                <div className="text-right">
                  <p className="text-xs text-gray-600">{s.role}</p>
                  <span className={`badge ${s.status === 'active' ? 'badge-approved' : s.status === 'probation' ? 'badge-pending' : 'badge-review'}`}>{s.status}</span>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="space-y-4">
          <div className="bg-white border border-gray-100 rounded-xl p-5">
            <SectionHeader title="Staff by Department" />
            <ResponsiveContainer width="100%" height={160}>
              <BarChart data={deptData}>
                <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
                <XAxis dataKey="dept" tick={{ fontSize: 9 }} />
                <YAxis tick={{ fontSize: 11 }} />
                <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8 }} />
                <Bar dataKey="count" fill="#15803d" radius={[4,4,0,0]} name="Staff" />
              </BarChart>
            </ResponsiveContainer>
          </div>

          <div className="bg-white border border-gray-100 rounded-xl p-5">
            <SectionHeader title="Open Positions" />
            {[
              { title: 'Senior Lecturer – Tourism', dept: 'School of Tourism', deadline: 'Aug 1, 2025' },
              { title: 'Lab Technician – Culinary', dept: 'School of Culinary', deadline: 'Jul 30, 2025' },
              { title: 'Finance Assistant', dept: 'Finance', deadline: 'Aug 5, 2025' },
            ].map(v => (
              <div key={v.title} className="py-2.5 border-b border-gray-50 last:border-0">
                <p className="text-xs font-600 text-gray-800">{v.title}</p>
                <p className="text-[11px] text-gray-400">{v.dept} · Deadline: {v.deadline}</p>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}

// ── QA OFFICER DASHBOARD ──────────────────────────────────────────────────────

function QADash() {
  const COLORS = { above: '#15803d', at: '#0d9488', near: '#d97706', below: '#dc2626' }
  const statusLabel = { above: 'Above Target', at: 'At Target', near: 'Near Target', below: 'Below Target' }

  return (
    <div className="space-y-5">
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <StatCard label="Overall QA Score" value="82.2" sub="/100 composite" icon={<Star size={20} />} />
        <StatCard label="Areas Above Target" value="2" sub="of 6 areas" icon={<CheckCircle size={20} />} />
        <StatCard label="Areas Needing Attention" value="1" sub="Research Output" color="#dc2626" icon={<AlertTriangle size={20} />} />
        <StatCard label="Next Audit" value="Aug 2025" sub="Internal audit" icon={<Clock size={20} />} color="#1d4ed8" />
      </div>

      <div className="grid md:grid-cols-2 gap-5">
        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <SectionHeader title="QA Metrics Dashboard" subtitle="Current academic year scores vs targets" />
          <div className="space-y-4">
            {QA_METRICS.map(m => (
              <div key={m.area}>
                <div className="flex items-center justify-between mb-1">
                  <span className="text-xs font-500 text-gray-700">{m.area}</span>
                  <div className="flex items-center gap-2">
                    <span className="text-xs font-600" style={{ color: COLORS[m.status as keyof typeof COLORS] }}>{m.score}%</span>
                    <span className="text-[10px] text-gray-400">/ {m.target}%</span>
                  </div>
                </div>
                <div className="relative bg-gray-100 rounded-full h-2.5">
                  <div className="h-full rounded-full transition-all" style={{ width: `${m.score}%`, background: COLORS[m.status as keyof typeof COLORS] }} />
                  <div className="absolute top-0 h-full border-l-2 border-dashed border-gray-400 opacity-50" style={{ left: `${m.target}%` }} />
                </div>
                <p className="text-[10px] mt-0.5" style={{ color: COLORS[m.status as keyof typeof COLORS] }}>
                  {statusLabel[m.status as keyof typeof statusLabel]}
                </p>
              </div>
            ))}
          </div>
        </div>

        <div className="space-y-4">
          <div className="bg-white border border-gray-100 rounded-xl p-5">
            <SectionHeader title="Accreditation Status" />
            {[
              { body: 'Commission for University Education (CUE)', status: 'Valid', expiry: 'Dec 2026' },
              { body: 'NACTVET', status: 'Valid', expiry: 'Mar 2026' },
              { body: 'ISO 9001:2015', status: 'Valid', expiry: 'Nov 2025' },
              { body: 'EAC Recognition', status: 'Valid', expiry: 'Sep 2027' },
            ].map(a => (
              <div key={a.body} className="flex items-center justify-between py-2.5 border-b border-gray-50 last:border-0">
                <div>
                  <p className="text-xs font-500 text-gray-800">{a.body}</p>
                  <p className="text-[11px] text-gray-400">Expires: {a.expiry}</p>
                </div>
                <span className="badge badge-approved">{a.status}</span>
              </div>
            ))}
          </div>

          <div className="bg-white border border-gray-100 rounded-xl p-5">
            <SectionHeader title="Upcoming Audits" />
            {[
              { name: 'Internal QA Audit', date: 'Aug 14, 2025', type: 'Internal' },
              { name: 'TCU Inspection Visit', date: 'Oct 10, 2025', type: 'External' },
              { name: 'Student Satisfaction Survey', date: 'Sep 5, 2025', type: 'Internal' },
            ].map(a => (
              <div key={a.name} className="py-2.5 border-b border-gray-50 last:border-0">
                <div className="flex items-center justify-between">
                  <p className="text-xs font-500 text-gray-800">{a.name}</p>
                  <span className={`badge ${a.type === 'External' ? 'badge-review' : 'badge-active'}`}>{a.type}</span>
                </div>
                <p className="text-[11px] text-gray-400 mt-0.5">{a.date}</p>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}

// ── GENERIC FALLBACK PAGES ─────────────────────────────────────────────────────

function StudentsPage() {
  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <StatCard label="Total Students" value="1,640" sub="Active enrolment" icon={<GraduationCap size={20} />} />
        <StatCard label="New This Intake" value="342" sub="Sept 2025" trend="up" icon={<UserCheck size={20} />} color="#0d9488" />
        <StatCard label="Graduating" value="218" sub="Class of 2025" icon={<Award size={20} />} color="#1d4ed8" />
        <StatCard label="International" value="87" sub="5.3% of total" icon={<Users size={20} />} color="#7c3aed" />
      </div>
      <div className="bg-white border border-gray-100 rounded-xl p-5">
        <SectionHeader title="Student Register" subtitle="Active students by program" />
        <div className="overflow-x-auto">
          <table className="w-full text-xs">
            <thead><tr className="border-b border-gray-100">
              {['Program', 'Faculty', 'Enrolled', 'Capacity', 'GPA Avg', 'Fill %'].map(h => (
                <th key={h} className="text-left py-2 px-3 text-gray-500 font-600">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {PROGRAMS.map(p => (
                <tr key={p.name} className="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                  <td className="py-2.5 px-3 font-500 text-gray-800">{p.name}</td>
                  <td className="py-2.5 px-3 text-gray-500">{p.department}</td>
                  <td className="py-2.5 px-3 font-600 text-green-700">{p.enrolled}</td>
                  <td className="py-2.5 px-3 text-gray-500">{p.capacity}</td>
                  <td className="py-2.5 px-3">3.{Math.floor(Math.random() * 4 + 1)}</td>
                  <td className="py-2.5 px-3">
                    <div className="flex items-center gap-2">
                      <div className="bg-gray-100 rounded-full h-1.5 w-16">
                        <div className="h-full bg-green-500 rounded-full" style={{ width: `${Math.round((p.enrolled / p.capacity) * 100)}%` }} />
                      </div>
                      <span>{Math.round((p.enrolled / p.capacity) * 100)}%</span>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function ProgramsPage() {
  const [deptFilter, setDeptFilter] = useState('')
  const [searchQuery, setSearchQuery] = useState('')

  const filtered = PROGRAMS.filter(p => {
    const matchesDept = deptFilter === '' || p.department === deptFilter
    const matchesSearch = searchQuery === '' ||
      p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      p.level.toLowerCase().includes(searchQuery.toLowerCase()) ||
      p.qualification.toLowerCase().includes(searchQuery.toLowerCase())
    return matchesDept && matchesSearch
  })

  return (
    <div className="space-y-4">
      <div className="flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1">
          <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            placeholder="Search programs..."
            value={searchQuery}
            onChange={e => setSearchQuery(e.target.value)}
            className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100"
          />
        </div>
        <select
          value={deptFilter}
          onChange={e => setDeptFilter(e.target.value)}
          className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white"
        >
          <option value="">All Departments</option>
          {DEPARTMENTS.map(d => (
            <option key={d} value={d}>{d}</option>
          ))}
        </select>
      </div>
      <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {filtered.map(p => (
          <div key={p.id} className="bg-white border border-gray-100 rounded-xl p-5 hover:shadow-md transition-shadow">
            <div className="flex items-start justify-between mb-3">
              <span className="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded font-medium">{p.duration}</span>
              <span className="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded font-medium">{p.level}</span>
            </div>
            <h3 className="font-700 text-sm text-gray-900 mb-1" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>{p.name}</h3>
            <p className="text-xs text-gray-500 mb-3">{p.department}</p>
            <div className="space-y-1.5">
              <div className="flex justify-between text-xs"><span className="text-gray-500">Enrolled</span><span className="font-600 text-green-700">{p.enrolled} / {p.capacity}</span></div>
              <div className="flex justify-between text-xs"><span className="text-gray-500">Fee</span><span className="font-600 text-gray-700">KES {p.fee.toLocaleString()}{p.feeNote ? ` (${p.feeNote})` : '/semester'}</span></div>
              <div className="flex justify-between text-xs"><span className="text-gray-500">Qualification</span><span className="font-600 text-gray-700">{p.qualification}</span></div>
            </div>
            <div className="mt-3 bg-gray-100 rounded-full h-1.5">
              <div className="h-full bg-green-500 rounded-full" style={{ width: `${Math.round((p.enrolled / p.capacity) * 100)}%` }} />
            </div>
          </div>
        ))}
      </div>
      {filtered.length === 0 && (
        <p className="text-center text-gray-400 text-sm mt-6">No programs match your filters.</p>
      )}
    </div>
  )
}

function ReportsPage() {
  return (
    <div className="bg-white border border-gray-100 rounded-xl p-8 text-center">
      <BarChart3 size={40} className="mx-auto text-green-600 mb-3 opacity-60" />
      <h3 className="font-700 text-gray-700 mb-2" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Reports & Analytics</h3>
      <p className="text-sm text-gray-400">Report generation module — export academic, financial, and operational reports.</p>
      <div className="mt-6 grid sm:grid-cols-3 gap-3 max-w-lg mx-auto">
        {['Academic Performance Report', 'Finance Summary Q2 2025', 'Student Enrolment Report', 'HR Headcount Report', 'QA Compliance Report', 'Admissions Pipeline Report'].map(r => (
          <button key={r} className="btn-outline text-xs py-2.5 flex items-center justify-center gap-1.5"><Download size={12} /> {r}</button>
        ))}
      </div>
    </div>
  )
}

function SettingsPage() {
  return (
    <div className="max-w-xl space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl p-5">
        <h3 className="font-700 text-gray-800 mb-4" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>System Settings</h3>
        {['Academic Year', 'Default Timezone', 'Currency', 'Max Applications per Intake'].map(s => (
          <div key={s} className="flex items-center justify-between py-3 border-b border-gray-50 last:border-0">
            <span className="text-sm text-gray-700">{s}</span>
            <input className="border border-gray-200 rounded-lg px-2 py-1 text-xs w-40 focus:outline-none focus:border-green-400" placeholder="Configure..." />
          </div>
        ))}
      </div>
    </div>
  )
}

// ── MAIN EXPORT ───────────────────────────────────────────────────────────────

type Page = 'dashboard' | 'approvals' | 'students' | 'staff' | 'finance' | 'programs' | 'reports' | 'settings' | 'hr' | 'qa' | 'hr-leave' | 'hr-payroll' | 'hr-attendance' | 'hr-expenses' | 'hr-documents' | 'hr-reports' | 'hr-employees' | 'finance-overview' | 'finance-invoices' | 'finance-bills' | 'finance-banking' | 'finance-accounts' | 'finance-reports' | 'finance-customers' | 'finance-vendors' | 'finance-journal' | 'academics-overview' | 'academics-students' | 'academics-programs' | 'academics-admissions' | 'academics-staff' | 'academics-examinations' | 'academics-timetable' | 'academics-departments' | 'academics-records' | 'academics-courses' | 'admissions-overview' | 'admissions-applications' | 'admissions-reviews' | 'admissions-shortlisted' | 'admissions-offers' | 'admissions-registered' | 'cms' | 'events' | 'qa-overview' | 'qa-quality-plans' | 'qa-training' | 'qa-audits' | 'qa-assessments' | 'qa-self-audits' | 'qa-corrective-actions' | 'qa-reports'

export function renderDashboard(role: Role, page: Page) {
  if (page === 'hr') return renderHRPages('overview')
  if (page.startsWith('hr-')) {
    const subPage = page.replace('hr-', '') as 'overview' | 'employees' | 'onboarding' | 'leave' | 'attendance' | 'payroll' | 'expenses' | 'documents' | 'reports' | 'training' | 'analytics'
    return renderHRPages(subPage)
  }
  if (page === 'finance-overview') return renderFinancePages('overview')
  if (page.startsWith('finance-')) {
    const subPage = page.replace('finance-', '') as 'overview' | 'invoices' | 'bills' | 'banking' | 'accounts' | 'reports' | 'customers' | 'vendors' | 'journal'
    return renderFinancePages(subPage)
  }
  if (page.startsWith('academics-')) {
    const subPage = page.replace('academics-', '') as 'overview' | 'students' | 'programs' | 'admissions' | 'staff' | 'examinations' | 'timetable' | 'departments' | 'records' | 'courses'
    return renderAcademicsPages(subPage)
  }
  if (page.startsWith('admissions-')) {
    const subPage = page.replace('admissions-', '') as 'overview' | 'applications' | 'reviews' | 'shortlisted' | 'offers' | 'registered'
    return renderAdmissionsPages(subPage)
  }
  if (page.startsWith('qa-')) {
    const subPage = page.replace('qa-', '') as 'overview' | 'quality-plans' | 'training' | 'audits' | 'assessments' | 'self-audits' | 'corrective-actions' | 'reports'
    return renderQAPages(subPage)
  }
  if (page === 'approvals') return <ApprovalsPanel role={role} />
  if (page === 'students') return <StudentsPage />
  if (page === 'programs') return <ProgramsPage />
  if (page === 'finance') return <FinanceDash />
  if (page === 'qa') return <QADash />
  if (page === 'reports') return <ReportsPage />
  if (page === 'settings') return <SettingsPage />
  if (page === 'staff') return <HRDash />
  if (page === 'cms') return <CMSDash />
  if (page === 'events') return <EventsDash />

  switch (role) {
    case 'super_admin': return <SuperAdminDash />
    case 'ceo': return <CEODash />
    case 'academic_registrar': return <RegistrarDash />
    case 'hod': return <HODDash />
    case 'admissions_officer': return <AdmissionsDash />
    case 'finance_manager': return <FinanceDash />
    case 'hr_manager': return <HRDash />
    case 'qa_officer': return <QADash />
    default: return <SuperAdminDash />
  }
}
