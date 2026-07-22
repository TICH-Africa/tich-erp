import { useState } from 'react'
import {
  QUALITY_PLANS, TRAINING_SESSIONS, AUDIT_LOGS, ASSESSMENT_SHEETS,
  SELF_AUDITS, CORRECTIVE_ACTIONS, QA_METRICS,
  type QualityPlan, type TrainingSession, type AuditLog, type AssessmentSheet,
  type SelfAudit, type CorrectiveAction
} from '@/data/mock'
import {
  Search, ChevronRight, Plus, Download,
  AlertTriangle,
  BarChart3, FileText, ClipboardList, GraduationCap, Shield, FileCheck, TrendingUp
} from 'lucide-react'

interface Props {
  initialSubPage?: QASubPage
}

type QASubPage = 'overview' | 'quality-plans' | 'training' | 'audits' | 'assessments' | 'self-audits' | 'corrective-actions' | 'reports'

export default function QAPages({ initialSubPage = 'overview' }: Props) {
  const [activeSubPage, setActiveSubPage] = useState<QASubPage>(initialSubPage)

  const subNavItems: { label: string; page: QASubPage; icon: React.ReactNode }[] = [
    { label: 'Command Center', page: 'overview', icon: <BarChart3 size={15} /> },
    { label: 'Quality Plans', page: 'quality-plans', icon: <ClipboardList size={15} /> },
    { label: 'Training', page: 'training', icon: <GraduationCap size={15} /> },
    { label: 'Audit Logs', page: 'audits', icon: <FileText size={15} /> },
    { label: 'Assessments', page: 'assessments', icon: <FileCheck size={15} /> },
    { label: 'Self-Audits', page: 'self-audits', icon: <Shield size={15} /> },
    { label: 'Corrective Actions', page: 'corrective-actions', icon: <AlertTriangle size={15} /> },
    { label: 'Reports', page: 'reports', icon: <Download size={15} /> },
  ]

  return (
    <div className="space-y-5">
      <div className="bg-white border border-gray-100 rounded-xl p-1.5 flex gap-1 overflow-x-auto">
        {subNavItems.map(({ label, page, icon }) => (
          <button key={page} onClick={() => setActiveSubPage(page)}
            className={`flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium whitespace-nowrap transition-colors ${activeSubPage === page ? 'bg-cyan-50 text-cyan-700' : 'text-gray-600 hover:bg-gray-50'}`}>
            {icon}
            <span className="hidden sm:inline">{label}</span>
          </button>
        ))}
      </div>

      {activeSubPage === 'overview' && <QAOverview onNavigate={(page) => setActiveSubPage(page)} />}
      {activeSubPage === 'quality-plans' && <QualityPlansPage plans={QUALITY_PLANS} />}
      {activeSubPage === 'training' && <TrainingPage sessions={TRAINING_SESSIONS} />}
      {activeSubPage === 'audits' && <AuditLogsPage logs={AUDIT_LOGS} />}
      {activeSubPage === 'assessments' && <AssessmentsPage sheets={ASSESSMENT_SHEETS} />}
      {activeSubPage === 'self-audits' && <SelfAuditsPage audits={SELF_AUDITS} />}
      {activeSubPage === 'corrective-actions' && <CorrectiveActionsPage actions={CORRECTIVE_ACTIONS} />}
      {activeSubPage === 'reports' && <QAReportsPage />}
    </div>
  )
}

export function renderQAPages(subPage: QASubPage) {
  return <QAPages key={subPage} initialSubPage={subPage} />
}

function QAOverview({ onNavigate }: { onNavigate: (page: QASubPage) => void }) {
  const activePlans = QUALITY_PLANS.filter(p => p.status === 'active').length
  const pendingAudits = SELF_AUDITS.filter(a => a.status === 'in_progress' || a.status === 'pending').length
  const openActions = CORRECTIVE_ACTIONS.filter(a => a.status === 'open' || a.status === 'in_progress').length
  const avgScore = Math.round(QUALITY_PLANS.reduce((s, p) => s + p.currentScore, 0) / QUALITY_PLANS.length)

  return (
    <div className="space-y-5">
      <div className="bg-gradient-to-r from-cyan-800 to-cyan-700 rounded-xl p-5 text-white">
        <p className="text-xs text-cyan-200 font-medium mb-1">Quality Assurance Command Center</p>
        <h2 className="text-xl font-extrabold">QA Overview</h2>
        <p className="text-sm text-cyan-200 mt-1">Monitor compliance, audits, and quality metrics across all departments.</p>
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <StatCard label="Active Quality Plans" value={String(activePlans)} sub="Across all departments" icon={<ClipboardList size={22} />} />
        <StatCard label="Pending Audits" value={String(pendingAudits)} sub="Self-audits in progress" icon={<Shield size={22} />} color="#d97706" />
        <StatCard label="Corrective Actions" value={String(openActions)} sub="Require attention" icon={<AlertTriangle size={22} />} color="#dc2626" />
        <StatCard label="Avg Compliance Score" value={`${avgScore}%`} sub="Quality plans" icon={<TrendingUp size={22} />} color="#15803d" />
      </div>

      <div className="grid md:grid-cols-2 gap-5">
        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <h3 className="font-bold text-gray-800 mb-4">Recent Audit Exceptions</h3>
          <div className="space-y-3">
            {AUDIT_LOGS.slice(0, 4).map(log => (
              <div key={log.id} className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                  <p className="text-xs font-semibold text-gray-800">{log.action}</p>
                  <p className="text-[11px] text-gray-400">{log.department} · {log.timestamp}</p>
                </div>
                <span className={`badge ${log.severity === 'critical' ? 'badge-rejected' : log.severity === 'warning' ? 'badge-pending' : 'badge-approved'}`}>{log.severity}</span>
              </div>
            ))}
          </div>
        </div>

        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <h3 className="font-bold text-gray-800 mb-4">QA Metrics Snapshot</h3>
          <div className="space-y-3">
            {QA_METRICS.map(m => (
              <div key={m.area}>
                <div className="flex items-center justify-between mb-1">
                  <span className="text-xs font-500 text-gray-700">{m.area}</span>
                  <span className="text-xs font-600 text-gray-700">{m.score}% / {m.target}%</span>
                </div>
                <div className="bg-gray-100 rounded-full h-2">
                  <div className="h-full rounded-full" style={{ width: `${m.score}%`, background: m.status === 'above' ? '#15803d' : m.status === 'at' ? '#0d9488' : m.status === 'near' ? '#d97706' : '#dc2626' }} />
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">Open Corrective Actions</h3>
          <button onClick={() => onNavigate('corrective-actions')} className="text-xs text-cyan-700 font-semibold flex items-center gap-1 hover:gap-2 transition-all">View All <ChevronRight size={14} /></button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['ID', 'Title', 'Department', 'Severity', 'Status', 'Due Date'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {CORRECTIVE_ACTIONS.filter(a => a.status !== 'resolved').slice(0, 5).map(action => (
                <tr key={action.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{action.id}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-800">{action.title}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{action.department}</td>
                  <td className="py-3 px-4"><span className={`badge ${action.severity === 'critical' ? 'badge-rejected' : action.severity === 'high' ? 'badge-pending' : 'badge-approved'}`}>{action.severity}</span></td>
                  <td className="py-3 px-4"><span className={`badge ${action.status === 'open' ? 'badge-pending' : 'badge-review'}`}>{action.status}</span></td>
                  <td className="py-3 px-4 text-xs text-gray-600">{action.dueDate}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function StatCard({ label, value, sub, color = '#15803d', icon }: {
  label: string; value: string; sub?: string; color?: string; icon: React.ReactNode
}) {
  return (
    <div className="bg-white border border-gray-100 rounded-xl p-4">
      <div className="flex items-start justify-between">
        <div>
          <p className="text-xs text-gray-500 font-medium">{label}</p>
          <p className="text-2xl font-extrabold mt-1 text-gray-900">{value}</p>
          {sub && <p className="text-xs mt-1 text-gray-400">{sub}</p>}
        </div>
        <div className="w-10 h-10 rounded-xl flex items-center justify-center" style={{ background: color + '18' }}>
          <span style={{ color }}>{icon}</span>
        </div>
      </div>
    </div>
  )
}

function QualityPlansPage({ plans }: { plans: QualityPlan[] }) {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const filtered = plans.filter(p => {
    const matchesSearch = p.title.toLowerCase().includes(search.toLowerCase()) || p.department.toLowerCase().includes(search.toLowerCase())
    const matchesStatus = statusFilter === '' || p.status === statusFilter
    return matchesSearch && matchesStatus
  })
  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">Quality Assurance Plans</h3>
          <button className="bg-cyan-700 text-white flex items-center gap-2 px-4 py-2 rounded-lg text-sm hover:bg-cyan-800"><Plus size={16} /> New Plan</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search plans..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
              <option value="">All Status</option>
              <option value="draft">Draft</option>
              <option value="active">Active</option>
              <option value="completed">Completed</option>
              <option value="archived">Archived</option>
            </select>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Plan ID', 'Title', 'Department', 'Target', 'Current', 'Status', 'Start Date', 'End Date'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(p => (
                <tr key={p.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{p.id}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-800">{p.title}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{p.department}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{p.targetScore}%</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{p.currentScore}%</td>
                  <td className="py-3 px-4"><span className={`badge ${p.status === 'active' ? 'badge-approved' : p.status === 'draft' ? 'badge-pending' : p.status === 'completed' ? 'badge-approved' : 'badge-review'}`}>{p.status}</span></td>
                  <td className="py-3 px-4 text-xs text-gray-600">{p.startDate}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{p.endDate}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function TrainingPage({ sessions }: { sessions: TrainingSession[] }) {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const filtered = sessions.filter(s => {
    const matchesSearch = s.title.toLowerCase().includes(search.toLowerCase()) || s.trainer.toLowerCase().includes(search.toLowerCase())
    const matchesStatus = statusFilter === '' || s.status === statusFilter
    return matchesSearch && matchesStatus
  })
  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">Capacity Building & Training</h3>
          <button className="bg-cyan-700 text-white flex items-center gap-2 px-4 py-2 rounded-lg text-sm hover:bg-cyan-800"><Plus size={16} /> Schedule Training</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search training..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
              <option value="">All Status</option>
              <option value="scheduled">Scheduled</option>
              <option value="ongoing">Ongoing</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Session ID', 'Title', 'Trainer', 'Department', 'Date', 'Duration', 'Attendees', 'Status', 'Type'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(s => (
                <tr key={s.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{s.id}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-800">{s.title}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{s.trainer}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{s.department}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{s.date}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{s.duration}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{s.attendees}</td>
                  <td className="py-3 px-4"><span className={`badge ${s.status === 'completed' ? 'badge-approved' : s.status === 'scheduled' ? 'badge-pending' : 'badge-review'}`}>{s.status}</span></td>
                  <td className="py-3 px-4 text-xs text-gray-600">{s.type.replace('_', ' ')}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function AuditLogsPage({ logs }: { logs: AuditLog[] }) {
  const [search, setSearch] = useState('')
  const [severityFilter, setSeverityFilter] = useState('')
  const [resolvedFilter, setResolvedFilter] = useState('')
  const filtered = logs.filter(log => {
    const matchesSearch = log.action.toLowerCase().includes(search.toLowerCase()) || log.department.toLowerCase().includes(search.toLowerCase()) || log.user.toLowerCase().includes(search.toLowerCase())
    const matchesSeverity = severityFilter === '' || log.severity === severityFilter
    const matchesResolved = resolvedFilter === '' || (resolvedFilter === 'resolved' ? log.resolved : !log.resolved)
    return matchesSearch && matchesSeverity && matchesResolved
  })
  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">Unalterable Audit Logs</h3>
          <button className="bg-cyan-700 text-white flex items-center gap-2 px-4 py-2 rounded-lg text-sm hover:bg-cyan-800"><Download size={16} /> Export Report</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search audit logs..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" />
            </div>
            <select value={severityFilter} onChange={e => setSeverityFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
              <option value="">All Severity</option>
              <option value="info">Info</option>
              <option value="warning">Warning</option>
              <option value="critical">Critical</option>
            </select>
            <select value={resolvedFilter} onChange={e => setResolvedFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
              <option value="">All</option>
              <option value="resolved">Resolved</option>
              <option value="open">Open</option>
            </select>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Log ID', 'Action', 'Department', 'User', 'Timestamp', 'Severity', 'Status', 'Resolution'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(log => (
                <tr key={log.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{log.id}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-800">{log.action}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{log.department}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{log.user}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{log.timestamp}</td>
                  <td className="py-3 px-4"><span className={`badge ${log.severity === 'critical' ? 'badge-rejected' : log.severity === 'warning' ? 'badge-pending' : 'badge-approved'}`}>{log.severity}</span></td>
                  <td className="py-3 px-4"><span className={`badge ${log.resolved ? 'badge-approved' : 'badge-pending'}`}>{log.resolved ? 'Resolved' : 'Open'}</span></td>
                  <td className="py-3 px-4 text-xs text-gray-600 max-w-[200px] truncate">{log.resolution || '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function AssessmentsPage({ sheets }: { sheets: AssessmentSheet[] }) {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const filtered = sheets.filter(s => {
    const matchesSearch = s.title.toLowerCase().includes(search.toLowerCase()) || s.department.toLowerCase().includes(search.toLowerCase())
    const matchesStatus = statusFilter === '' || s.status === statusFilter
    return matchesSearch && matchesStatus
  })
  const formTypeLabel = (type: string) => type === 'chd_nursing' ? 'CHD & Nursing' : type === 'vocational' ? 'Vocational Training' : type === 'ict' ? 'ICT' : type === 'admissions' ? 'Admissions' : 'General'
  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">Assessment Sheet Orchestration</h3>
          <button className="bg-cyan-700 text-white flex items-center gap-2 px-4 py-2 rounded-lg text-sm hover:bg-cyan-800"><Plus size={16} /> Create Assessment</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search assessments..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
              <option value="">All Status</option>
              <option value="draft">Draft</option>
              <option value="dispatched">Dispatched</option>
              <option value="in_progress">In Progress</option>
              <option value="evidence_pending">Evidence Pending</option>
              <option value="submitted">Submitted</option>
              <option value="compiled">Compiled</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Sheet ID', 'Title', 'Department', 'Form Type', 'Status', 'Dispatched', 'Due Date', 'Score', 'Assigned To'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(s => (
                <tr key={s.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{s.id}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-800">{s.title}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{s.department}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{formTypeLabel(s.formType)}</td>
                  <td className="py-3 px-4"><span className={`badge ${s.status === 'submitted' || s.status === 'compiled' ? 'badge-approved' : s.status === 'draft' ? 'badge-pending' : 'badge-review'}`}>{s.status.replace('_', ' ')}</span></td>
                  <td className="py-3 px-4 text-xs text-gray-600">{s.dispatchedDate || '—'}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{s.dueDate}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{s.complianceScore ? `${s.complianceScore}%` : '—'}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{s.assignedTo.join(', ')}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function SelfAuditsPage({ audits }: { audits: SelfAudit[] }) {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const filtered = audits.filter(a => {
    const matchesSearch = a.department.toLowerCase().includes(search.toLowerCase()) || a.findings.toLowerCase().includes(search.toLowerCase())
    const matchesStatus = statusFilter === '' || a.status === statusFilter
    return matchesSearch && matchesStatus
  })
  const auditTypeLabel = (type: string) => type === 'clinical_practice' ? 'Clinical Practice' : type === 'vocational_competency' ? 'Vocational Competency' : type === 'it_resources' ? 'IT Resources' : 'Admissions Verification'
  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">Departmental Self-Audits</h3>
          <button className="bg-cyan-700 text-white flex items-center gap-2 px-4 py-2 rounded-lg text-sm hover:bg-cyan-800"><Plus size={16} /> Initiate Audit</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search audits..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
              <option value="">All Status</option>
              <option value="pending">Pending</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="flagged">Flagged</option>
            </select>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Audit ID', 'Department', 'Audit Type', 'Period', 'Score', 'Status', 'Findings', 'Completed By', 'Completed At'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(a => (
                <tr key={a.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{a.id}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-800">{a.department}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{auditTypeLabel(a.auditType)}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{a.period}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{a.score ? `${a.score}%` : '—'}</td>
                  <td className="py-3 px-4"><span className={`badge ${a.status === 'completed' ? 'badge-approved' : a.status === 'flagged' ? 'badge-rejected' : 'badge-pending'}`}>{a.status}</span></td>
                  <td className="py-3 px-4 text-xs text-gray-600 max-w-[200px] truncate">{a.findings}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{a.completedBy || '—'}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{a.completedAt || '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function CorrectiveActionsPage({ actions }: { actions: CorrectiveAction[] }) {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const filtered = actions.filter(a => {
    const matchesSearch = a.title.toLowerCase().includes(search.toLowerCase()) || a.department.toLowerCase().includes(search.toLowerCase())
    const matchesStatus = statusFilter === '' || a.status === statusFilter
    return matchesSearch && matchesStatus
  })
  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">Corrective Actions</h3>
          <button className="bg-cyan-700 text-white flex items-center gap-2 px-4 py-2 rounded-lg text-sm hover:bg-cyan-800"><Plus size={16} /> New Action</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search corrective actions..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
              <option value="">All Status</option>
              <option value="open">Open</option>
              <option value="in_progress">In Progress</option>
              <option value="resolved">Resolved</option>
              <option value="escalated">Escalated</option>
            </select>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['ID', 'Title', 'Department', 'Severity', 'Status', 'Assigned To', 'Due Date', 'Created', 'Resolved At'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(a => (
                <tr key={a.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{a.id}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-800">{a.title}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{a.department}</td>
                  <td className="py-3 px-4"><span className={`badge ${a.severity === 'critical' ? 'badge-rejected' : a.severity === 'high' ? 'badge-pending' : 'badge-approved'}`}>{a.severity}</span></td>
                  <td className="py-3 px-4"><span className={`badge ${a.status === 'resolved' ? 'badge-approved' : 'badge-pending'}`}>{a.status}</span></td>
                  <td className="py-3 px-4 text-xs text-gray-600">{a.assignedTo}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{a.dueDate}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{a.createdAt}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{a.resolvedAt || '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function QAReportsPage() {
  return (
    <div className="space-y-5">
      <div className="bg-white border border-gray-100 rounded-xl p-5">
        <h3 className="font-bold text-gray-800 mb-4">Quality Level Reports</h3>
        <p className="text-xs text-gray-500 mb-4">Periodic compiled reports for executive management and the CEO.</p>
        <div className="space-y-3">
          {[
            { id: 'QLR-001', title: 'Q2 2025 Quality Level Report', period: 'Q2 2025', generated: '2025-07-01', status: 'published' },
            { id: 'QLR-002', title: 'Q1 2025 Quality Level Report', period: 'Q1 2025', generated: '2025-04-01', status: 'published' },
            { id: 'QLR-003', title: 'Q4 2024 Quality Level Report', period: 'Q4 2024', generated: '2025-01-05', status: 'archived' },
          ].map(report => (
            <div key={report.id} className="flex items-center justify-between py-3 border-b border-gray-50 last:border-0">
              <div>
                <p className="text-xs font-semibold text-gray-800">{report.title}</p>
                <p className="text-[11px] text-gray-400">Generated: {report.generated}</p>
              </div>
              <div className="flex items-center gap-2">
                <span className={`badge ${report.status === 'published' ? 'badge-approved' : 'badge-review'}`}>{report.status}</span>
                <button className="text-cyan-700 hover:text-cyan-800"><Download size={14} /></button>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}