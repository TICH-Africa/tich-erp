import { useState } from 'react'
import {
  EMPLOYEES, LEAVE_RECORDS, PAYROLL_RECORDS, ATTENDANCE_RECORDS,
  DOCUMENTS, STATUTORY_REPORTS, EXPENSE_CLAIMS, DEPARTMENTS
} from '@/data/mock'
import {
  Users, UserPlus, Calendar, DollarSign, FileText, Upload,
  Search, ChevronRight, CheckCircle, XCircle, ClockIcon, Download,
  Award, Wallet, Receipt, BarChart3, TrendingUp, FileCheck
} from 'lucide-react'

type HRSubPage = 'overview' | 'employees' | 'onboarding' | 'leave' | 'attendance' | 'payroll' | 'expenses' | 'documents' | 'reports' | 'training' | 'analytics'

interface Props {
  initialSubPage?: 'overview' | 'employees' | 'onboarding' | 'leave' | 'attendance' | 'payroll' | 'expenses' | 'documents' | 'reports' | 'training' | 'analytics'
}

export default function HRPages({ initialSubPage = 'overview' }: Props) {
  const [activeSubPage, setActiveSubPage] = useState<HRSubPage>(initialSubPage)

  const subNavItems: { label: string; page: HRSubPage; icon: React.ReactNode }[] = [
    { label: 'Overview', page: 'overview', icon: <BarChart3 size={15} /> },
    { label: 'Employees', page: 'employees', icon: <Users size={15} /> },
    { label: 'Onboarding', page: 'onboarding', icon: <UserPlus size={15} /> },
    { label: 'Leave', page: 'leave', icon: <Calendar size={15} /> },
    { label: 'Attendance', page: 'attendance', icon: <ClockIcon size={15} /> },
    { label: 'Payroll', page: 'payroll', icon: <Wallet size={15} /> },
    { label: 'Expenses', page: 'expenses', icon: <Receipt size={15} /> },
    { label: 'Documents', page: 'documents', icon: <FileText size={15} /> },
    { label: 'Reports', page: 'reports', icon: <Download size={15} /> },
    { label: 'Training', page: 'training', icon: <Award size={15} /> },
    { label: 'Analytics', page: 'analytics', icon: <TrendingUp size={15} /> },
  ]

  return (
    <div className="space-y-5">
      {/* HR Sub-navigation tabs */}
      <div className="bg-white border border-gray-100 rounded-xl p-1.5 flex gap-1 overflow-x-auto">
        {subNavItems.map(({ label, page, icon }) => (
          <button key={page} onClick={() => setActiveSubPage(page)}
            className={`flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium whitespace-nowrap transition-colors ${activeSubPage === page ? 'bg-green-50 text-green-700' : 'text-gray-600 hover:bg-gray-50'}`}>
            {icon}
            <span className="hidden sm:inline">{label}</span>
          </button>
        ))}
      </div>

      {activeSubPage === 'overview' && <HROverview />}
      {activeSubPage === 'employees' && <EmployeesPage />}
      {activeSubPage === 'onboarding' && <OnboardingPage />}
      {activeSubPage === 'leave' && <LeavePage />}
      {activeSubPage === 'attendance' && <AttendancePage />}
      {activeSubPage === 'payroll' && <PayrollPage />}
      {activeSubPage === 'expenses' && <ExpensesPage />}
      {activeSubPage === 'documents' && <DocumentsPage />}
      {activeSubPage === 'reports' && <ReportsPage />}
      {activeSubPage === 'training' && <TrainingPage />}
      {activeSubPage === 'analytics' && <AnalyticsPage />}
    </div>
  )
}

function HROverview() {
  const totalEmployees = EMPLOYEES.length
  const onLeave = EMPLOYEES.filter(e => e.status === 'on_leave').length
  const pendingLeave = LEAVE_RECORDS.filter(l => l.status === 'pending').length
  const totalPayroll = PAYROLL_RECORDS.reduce((sum, p) => sum + p.netPay, 0)

  return (
    <div className="space-y-5">
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <StatCard label="Total Employees" value={String(totalEmployees)} sub="Active workforce" icon={<Users size={22} />} />
        <StatCard label="On Leave Today" value={String(onLeave)} sub="Approved leave" icon={<Calendar size={22} />} color="#d97706" />
        <StatCard label="Pending Leave" value={String(pendingLeave)} sub="Awaiting approval" icon={<ClockIcon size={22} />} color="#dc2626" />
        <StatCard label="Total Payroll" value={`KES ${(totalPayroll / 1000000).toFixed(1)}M`} sub="July 2025" icon={<Wallet size={22} />} color="#1d4ed8" />
      </div>

      <div className="grid md:grid-cols-2 gap-5">
        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <h3 className="font-700 text-gray-800 mb-4" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Pending Approvals</h3>
          <div className="space-y-3">
            {LEAVE_RECORDS.filter(l => l.status === 'pending').map(leave => (
              <div key={leave.id} className="flex items-center justify-between p-3 bg-yellow-50 border border-yellow-100 rounded-lg">
                <div>
                  <p className="text-xs font-600 text-gray-800">{leave.employeeName}</p>
                  <p className="text-[11px] text-gray-500">{leave.leaveType} · {leave.days} days · {leave.startDate}</p>
                </div>
                <div className="flex gap-1.5">
                  <button className="p-1.5 bg-green-100 text-green-700 rounded hover:bg-green-200"><CheckCircle size={14} /></button>
                  <button className="p-1.5 bg-red-100 text-red-700 rounded hover:bg-red-200"><XCircle size={14} /></button>
                </div>
              </div>
            ))}
            {EXPENSE_CLAIMS.filter(e => e.status === 'pending').map(expense => (
              <div key={expense.id} className="flex items-center justify-between p-3 bg-blue-50 border border-blue-100 rounded-lg">
                <div>
                  <p className="text-xs font-600 text-gray-800">{expense.employeeName}</p>
                  <p className="text-[11px] text-gray-500">{expense.category} · KES {expense.amount.toLocaleString()}</p>
                </div>
                <div className="flex gap-1.5">
                  <button className="p-1.5 bg-green-100 text-green-700 rounded hover:bg-green-200"><CheckCircle size={14} /></button>
                  <button className="p-1.5 bg-red-100 text-red-700 rounded hover:bg-red-200"><XCircle size={14} /></button>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="space-y-4">
          <div className="bg-white border border-gray-100 rounded-xl p-5">
            <h3 className="font-700 text-gray-800 mb-3" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Quick Stats</h3>
            <div className="space-y-2.5">
              <div className="flex items-center justify-between">
                <span className="text-xs text-gray-600">Total Employees</span>
                <span className="text-xs font-600 text-gray-800">{totalEmployees}</span>
              </div>
              <div className="w-full bg-gray-100 rounded-full h-1.5">
                <div className="h-full bg-green-500 rounded-full" style={{ width: '100%' }} />
              </div>
              <div className="flex items-center justify-between">
                <span className="text-xs text-gray-600">Active</span>
                <span className="text-xs font-600 text-green-700">{EMPLOYEES.filter(e => e.status === 'active').length}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-xs text-gray-600">On Leave</span>
                <span className="text-xs font-600 text-yellow-600">{onLeave}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-xs text-gray-600">On Probation</span>
                <span className="text-xs font-600 text-orange-600">{EMPLOYEES.filter(e => e.status === 'probation').length}</span>
              </div>
            </div>
          </div>

          <div className="bg-white border border-gray-100 rounded-xl p-5">
            <h3 className="font-700 text-gray-800 mb-3" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Statutory Compliance</h3>
            <div className="space-y-2">
              {STATUTORY_REPORTS.map(report => (
                <div key={report.id} className="flex items-center justify-between">
                  <div>
                    <p className="text-xs font-500 text-gray-700">{report.name}</p>
                    <p className="text-[10px] text-gray-400">{report.period}</p>
                  </div>
                  <span className={`badge ${report.status === 'ready' ? 'badge-approved' : report.status === 'pending' ? 'badge-pending' : 'badge-review'}`}>{report.status}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

function EmployeesPage() {
  const [searchQuery, setSearchQuery] = useState('')
  const [deptFilter, setDeptFilter] = useState('')

  const filtered = EMPLOYEES.filter(emp => {
    const matchesSearch = emp.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      emp.jobTitle.toLowerCase().includes(searchQuery.toLowerCase()) ||
      emp.email.toLowerCase().includes(searchQuery.toLowerCase())
    const matchesDept = deptFilter === '' || emp.department === deptFilter
    return matchesSearch && matchesDept
  })

  return (
    <div className="space-y-4">
      <div className="flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1">
          <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input type="text" placeholder="Search employees..." value={searchQuery} onChange={e => setSearchQuery(e.target.value)}
            className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
        </div>
        <select value={deptFilter} onChange={e => setDeptFilter(e.target.value)}
          className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
          <option value="">All Departments</option>
          {DEPARTMENTS.map(d => <option key={d} value={d}>{d}</option>)}
        </select>
        <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><UserPlus size={16} /> Add Employee</button>
      </div>

      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Employee', 'Department', 'Job Title', 'Employment Type', 'Status', 'Salary'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(emp => (
                <tr key={emp.id} className="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                  <td className="py-3 px-4">
                    <div className="flex items-center gap-2.5">
                      <div className="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-xs font-700" style={{ fontWeight: 700 }}>{emp.avatar}</div>
                      <div>
                        <p className="text-xs font-600 text-gray-800">{emp.name}</p>
                        <p className="text-[10px] text-gray-400">{emp.email}</p>
                      </div>
                    </div>
                  </td>
                  <td className="py-3 px-4 text-xs text-gray-600">{emp.department}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{emp.jobTitle}</td>
                  <td className="py-3 px-4"><span className="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{emp.employmentType}</span></td>
                  <td className="py-3 px-4"><span className={`badge ${emp.status === 'active' ? 'badge-approved' : emp.status === 'probation' ? 'badge-pending' : emp.status === 'on_leave' ? 'badge-review' : 'bg-gray-100 text-gray-600'}`}>{emp.status}</span></td>
                  <td className="py-3 px-4 text-xs font-600 text-gray-700">KES {emp.salary.toLocaleString()}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {filtered.length === 0 && <p className="text-center text-gray-400 text-sm py-8">No employees found.</p>}
      </div>
    </div>
  )
}

function OnboardingPage() {
  const [onboardingSteps] = useState([
    { step: 1, title: 'Personal Details', description: 'Name, DOB, ID, contact info', status: 'completed' },
    { step: 2, title: 'Employment Details', description: 'Department, job title, line manager', status: 'completed' },
    { step: 3, title: 'Compensation', description: 'Salary, allowances, payment method', status: 'completed' },
    { step: 4, title: 'Statutory IDs', description: 'KRA PIN, NSSF, NHIF numbers', status: 'completed' },
    { step: 5, title: 'Documents Upload', description: 'ID, certificates, contract', status: 'in_progress' },
    { step: 6, title: 'System Access', description: 'ERP login, email, permissions', status: 'pending' },
  ])

  return (
    <div className="space-y-5">
      <div className="bg-white border border-gray-100 rounded-xl p-6">
        <h3 className="font-700 text-gray-800 mb-4" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>New Employee Onboarding</h3>
        <p className="text-xs text-gray-500 mb-6">Track onboarding progress for new hires. Each step must be completed before the employee becomes payroll-eligible.</p>
        <div className="space-y-4">
          {onboardingSteps.map((step, i) => (
            <div key={step.step} className="flex items-start gap-4">
              <div className="flex flex-col items-center">
                <div className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-700 ${step.status === 'completed' ? 'bg-green-100 text-green-700' : step.status === 'in_progress' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-400'}`} style={{ fontWeight: 700 }}>
                  {step.status === 'completed' ? <CheckCircle size={16} /> : step.step}
                </div>
                {i < onboardingSteps.length - 1 && <div className="w-0.5 h-8 bg-gray-200 mt-1" />}
              </div>
              <div className="flex-1 pb-4">
                <p className="text-sm font-600 text-gray-800">{step.title}</p>
                <p className="text-xs text-gray-500">{step.description}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}

function LeavePage() {
  const [filter, setFilter] = useState('')
  const filtered = LEAVE_RECORDS.filter(l => l.status === filter || filter === '')

  return (
    <div className="space-y-4">
      <div className="flex gap-2">
        {['', 'pending', 'approved', 'rejected', 'cancelled'].map(status => (
          <button key={status || 'all'} onClick={() => setFilter(status)}
            className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-colors ${filter === status ? 'bg-green-100 text-green-700' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'}`}>
            {status || 'All'}
          </button>
        ))}
      </div>

      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Employee', 'Leave Type', 'Dates', 'Days', 'Status', 'Reliever', 'Approved By'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(leave => (
                <tr key={leave.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-600 text-gray-800">{leave.employeeName}</td>
                  <td className="py-3 px-4"><span className="text-xs bg-green-50 text-green-700 px-2 py-0.5 rounded">{leave.leaveType}</span></td>
                  <td className="py-3 px-4 text-xs text-gray-600">{leave.startDate} – {leave.endDate}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{leave.days}</td>
                  <td className="py-3 px-4"><span className={`badge ${leave.status === 'approved' ? 'badge-approved' : leave.status === 'pending' ? 'badge-pending' : leave.status === 'rejected' ? 'badge-rejected' : 'badge-review'}`}>{leave.status}</span></td>
                  <td className="py-3 px-4 text-xs text-gray-600">{leave.reliever || '—'}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{leave.approvedBy || '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function AttendancePage() {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const filtered = ATTENDANCE_RECORDS.filter(att => {
    const matchesSearch = att.employeeName.toLowerCase().includes(search.toLowerCase())
    const matchesStatus = statusFilter === '' || att.status === statusFilter
    return matchesSearch && matchesStatus
  })

  return (
    <div className="space-y-4">
      <div className="flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1">
          <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input type="text" placeholder="Search employees..." value={search} onChange={e => setSearch(e.target.value)}
            className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
        </div>
        <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)}
          className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
          <option value="">All Status</option>
          <option value="present">Present</option>
          <option value="late">Late</option>
          <option value="leave">On Leave</option>
          <option value="absent">Absent</option>
        </select>
      </div>
      <div className="bg-white border border-gray-100 rounded-xl p-4">
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Today's Attendance</h3>
          <span className="text-xs text-gray-400">{new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</span>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Employee', 'Clock In', 'Clock Out', 'Hours', 'Overtime', 'Status', 'Notes'].map(h => (
                <th key={h} className="text-left py-2.5 px-3 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(att => (
                <tr key={att.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-2.5 px-3">
                    <div className="flex items-center gap-2">
                      <div className="w-7 h-7 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-[10px] font-700" style={{ fontWeight: 700 }}>
                        {att.employeeName.split(' ').map(n => n[0]).join('').slice(0, 2)}
                      </div>
                      <span className="text-xs font-600 text-gray-800">{att.employeeName}</span>
                    </div>
                  </td>
                  <td className="py-2.5 px-3 text-xs text-gray-600">{att.clockIn}</td>
                  <td className="py-2.5 px-3 text-xs text-gray-600">{att.clockOut}</td>
                  <td className="py-2.5 px-3 text-xs text-gray-600">{att.hoursWorked}h</td>
                  <td className="py-2.5 px-3 text-xs text-gray-600">{att.overtime > 0 ? `+${att.overtime}h` : '—'}</td>
                  <td className="py-2.5 px-3"><span className={`badge ${att.status === 'present' ? 'badge-approved' : att.status === 'late' ? 'badge-pending' : att.status === 'leave' ? 'badge-review' : 'bg-gray-100 text-gray-600'}`}>{att.status}</span></td>
                  <td className="py-2.5 px-3 text-xs text-gray-500">{att.notes || '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function PayrollPage() {
  const [search, setSearch] = useState('')
  const [deptFilter, setDeptFilter] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const filtered = PAYROLL_RECORDS.filter(pay => {
    const matchesSearch = pay.employeeName.toLowerCase().includes(search.toLowerCase())
    const matchesDept = deptFilter === '' || pay.department === deptFilter
    const matchesStatus = statusFilter === '' || pay.status === statusFilter
    return matchesSearch && matchesDept && matchesStatus
  })
  const totalGross = PAYROLL_RECORDS.reduce((s, p) => s + p.grossSalary, 0)
  const totalDeductions = PAYROLL_RECORDS.reduce((s, p) => s + p.paye + p.nssf + p.nhif + p.housingLevy + p.otherDeductions, 0)
  const totalNet = PAYROLL_RECORDS.reduce((s, p) => s + p.netPay, 0)

  return (
    <div className="space-y-5">
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <StatCard label="Total Gross" value={`KES ${(totalGross / 1000000).toFixed(1)}M`} sub="July 2025" icon={<Wallet size={22} />} />
        <StatCard label="Total Deductions" value={`KES ${(totalDeductions / 1000).toFixed(0)}K`} sub="Statutory + other" icon={<DollarSign size={22} />} color="#dc2626" />
        <StatCard label="Total Net Pay" value={`KES ${(totalNet / 1000000).toFixed(1)}M`} sub="Disbursed" icon={<TrendingUp size={22} />} color="#15803d" />
        <StatCard label="Employees Paid" value={String(PAYROLL_RECORDS.length)} sub="July 2025" icon={<Users size={22} />} color="#1d4ed8" />
      </div>

      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Payroll Register - July 2025</h3>
          <div className="flex gap-2">
            <div className="relative">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search..." value={search} onChange={e => setSearch(e.target.value)}
                className="border border-gray-200 rounded-lg pl-9 pr-3 py-1.5 text-xs focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 w-40" />
            </div>
            <select value={deptFilter} onChange={e => setDeptFilter(e.target.value)}
              className="border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:border-green-500 bg-white">
              <option value="">All Depts</option>
              {DEPARTMENTS.map(d => <option key={d} value={d}>{d}</option>)}
            </select>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)}
              className="border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:border-green-500 bg-white">
              <option value="">All Status</option>
              <option value="paid">Paid</option>
              <option value="processed">Processed</option>
            </select>
            <button className="btn-outline text-xs flex items-center gap-1.5 px-3 py-1.5"><Download size={14} /> Export</button>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Employee', 'Department', 'Basic', 'Allowances', 'Gross', 'PAYE', 'NSSF', 'NHIF', 'Housing Levy', 'Other', 'Net Pay', 'Status'].map(h => (
                <th key={h} className="text-left py-2.5 px-3 text-gray-500 font-600 text-[11px]">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(pay => (
                <tr key={pay.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-2.5 px-3 text-xs font-600 text-gray-800">{pay.employeeName}</td>
                  <td className="py-2.5 px-3 text-xs text-gray-600">{pay.department}</td>
                  <td className="py-2.5 px-3 text-xs text-gray-600">{pay.basicSalary.toLocaleString()}</td>
                  <td className="py-2.5 px-3 text-xs text-gray-600">{pay.allowances.toLocaleString()}</td>
                  <td className="py-2.5 px-3 text-xs font-600 text-gray-700">{pay.grossSalary.toLocaleString()}</td>
                  <td className="py-2.5 px-3 text-xs text-red-600">{pay.paye.toLocaleString()}</td>
                  <td className="py-2.5 px-3 text-xs text-gray-600">{pay.nssf.toLocaleString()}</td>
                  <td className="py-2.5 px-3 text-xs text-gray-600">{pay.nhif.toLocaleString()}</td>
                  <td className="py-2.5 px-3 text-xs text-gray-600">{pay.housingLevy.toLocaleString()}</td>
                  <td className="py-2.5 px-3 text-xs text-gray-600">{pay.otherDeductions.toLocaleString()}</td>
                  <td className="py-2.5 px-3 text-xs font-600 text-green-700">{pay.netPay.toLocaleString()}</td>
                  <td className="py-2.5 px-3"><span className={`badge ${pay.status === 'paid' ? 'badge-approved' : pay.status === 'processed' ? 'badge-pending' : 'badge-review'}`}>{pay.status}</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function ExpensesPage() {
  const [statusFilter, setStatusFilter] = useState('all')
  const [search, setSearch] = useState('')
  const [categoryFilter, setCategoryFilter] = useState('')
  const filtered = EXPENSE_CLAIMS.filter(exp => {
    const matchesStatus = statusFilter === 'all' || exp.status === statusFilter
    const matchesSearch = exp.employeeName.toLowerCase().includes(search.toLowerCase()) || exp.id.toLowerCase().includes(search.toLowerCase())
    const matchesCategory = categoryFilter === '' || exp.category === categoryFilter
    return matchesStatus && matchesSearch && matchesCategory
  })

  return (
    <div className="space-y-4">
      <div className="flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1">
          <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input type="text" placeholder="Search expenses..." value={search} onChange={e => setSearch(e.target.value)}
            className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
        </div>
        <select value={categoryFilter} onChange={e => setCategoryFilter(e.target.value)}
          className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 bg-white">
          <option value="">All Categories</option>
          {[...new Set(EXPENSE_CLAIMS.map(e => e.category))].map(c => <option key={c} value={c}>{c}</option>)}
        </select>
        <div className="flex gap-2">
          {['all', 'pending', 'approved', 'paid', 'rejected'].map(status => (
            <button key={status} onClick={() => setStatusFilter(status)} className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-colors ${statusFilter === status ? 'bg-green-100 text-green-700' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'}`}>
              {status}
            </button>
          ))}
        </div>
      </div>

      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100">
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Expense Claims</h3>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['ID', 'Employee', 'Category', 'Amount', 'Date', 'Status', 'Approved By'].map(h => (
                <th key={h} className="text-left py-2.5 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(exp => (
                <tr key={exp.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-2.5 px-4 text-xs text-gray-500">{exp.id}</td>
                  <td className="py-2.5 px-4 text-xs font-600 text-gray-800">{exp.employeeName}</td>
                  <td className="py-2.5 px-4 text-xs text-gray-600">{exp.category}</td>
                  <td className="py-2.5 px-4 text-xs font-600 text-gray-700">KES {exp.amount.toLocaleString()}</td>
                  <td className="py-2.5 px-4 text-xs text-gray-600">{exp.date}</td>
                  <td className="py-2.5 px-4"><span className={`badge ${exp.status === 'paid' ? 'badge-approved' : exp.status === 'approved' ? 'badge-pending' : exp.status === 'pending' ? 'badge-review' : 'badge-rejected'}`}>{exp.status}</span></td>
                  <td className="py-2.5 px-4 text-xs text-gray-600">{exp.approvedBy || '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function DocumentsPage() {
  const [selectedEmp, setSelectedEmp] = useState('')
  const [search, setSearch] = useState('')
  const [categoryFilter, setCategoryFilter] = useState('')
  const filtered = DOCUMENTS.filter(d => {
    const matchesEmp = selectedEmp === '' || d.employeeId === selectedEmp
    const matchesSearch = d.title.toLowerCase().includes(search.toLowerCase()) || d.employeeName.toLowerCase().includes(search.toLowerCase())
    const matchesCategory = categoryFilter === '' || d.category === categoryFilter
    return matchesEmp && matchesSearch && matchesCategory
  })

  return (
    <div className="space-y-4">
      <div className="flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1">
          <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input type="text" placeholder="Search documents..." value={search} onChange={e => setSearch(e.target.value)}
            className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
        </div>
        <select value={selectedEmp} onChange={e => setSelectedEmp(e.target.value)}
          className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 bg-white">
          <option value="">All Employees</option>
          {EMPLOYEES.map(emp => <option key={emp.id} value={emp.id}>{emp.name}</option>)}
        </select>
        <select value={categoryFilter} onChange={e => setCategoryFilter(e.target.value)}
          className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 bg-white">
          <option value="">All Categories</option>
          {[...new Set(DOCUMENTS.map(d => d.category))].map(c => <option key={c} value={c}>{c}</option>)}
        </select>
        <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Upload size={16} /> Upload Document</button>
      </div>

      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Document', 'Employee', 'Category', 'Upload Date', 'Size', 'Type'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(doc => (
                <tr key={doc.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4">
                    <div className="flex items-center gap-2">
                      <FileText size={16} className="text-gray-400" />
                      <span className="text-xs font-600 text-gray-800">{doc.title}</span>
                    </div>
                  </td>
                  <td className="py-3 px-4 text-xs text-gray-600">{doc.employeeName}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{doc.category}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{doc.uploadDate}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{doc.size}</td>
                  <td className="py-3 px-4"><span className="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded uppercase">{doc.type}</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function ReportsPage() {
  const reports = [
    { name: 'PAYE Return', desc: 'Pay-As-You-Earn tax return to KRA', category: 'Statutory', icon: <FileCheck size={20} /> },
    { name: 'P9 Forms', desc: 'Annual tax certificates per employee', category: 'Statutory', icon: <FileText size={20} /> },
    { name: 'NSSF Return', desc: 'National Social Security Fund contributions', category: 'Statutory', icon: <FileCheck size={20} /> },
    { name: 'SHIF/NHIF Return', desc: 'Social Health Insurance Fund contributions', category: 'Statutory', icon: <FileCheck size={20} /> },
    { name: 'NITA Return', desc: 'National Industrial Training Authority levy', category: 'Statutory', icon: <FileCheck size={20} /> },
    { name: 'Housing Levy', desc: '1.5% Affordable Housing Levy', category: 'Statutory', icon: <FileCheck size={20} /> },
    { name: 'HELB', desc: 'Higher Education Loans Board deductions', category: 'Statutory', icon: <FileCheck size={20} /> },
    { name: 'Net Pay Register', desc: 'Who gets paid, by method, how much', category: 'Payroll', icon: <Wallet size={20} /> },
    { name: 'Salary Advance', desc: 'Outstanding advances and paid status', category: 'Payroll', icon: <Wallet size={20} /> },
    { name: 'Allowance Register', desc: 'All allowance lines by category', category: 'Payroll', icon: <Wallet size={20} /> },
    { name: 'Deduction Register', desc: 'All deduction lines by category', category: 'Payroll', icon: <Wallet size={20} /> },
    { name: 'Loan Repayment', desc: 'Active loans, balances, EMI', category: 'Payroll', icon: <Wallet size={20} /> },
    { name: 'Muster Roll', desc: 'Headcount & days-worked register', category: 'Attendance', icon: <ClockIcon size={20} /> },
    { name: 'Payroll Journal', desc: 'GL export for accounting integration', category: 'Finance', icon: <BarChart3 size={20} /> },
    { name: 'Payroll Liability', desc: 'Statutory + net pay liability', category: 'Finance', icon: <BarChart3 size={20} /> },
    { name: 'Pension Register', desc: 'Pension contributions', category: 'Statutory', icon: <FileCheck size={20} /> },
    { name: 'Leave Balances', desc: 'Leave taken, balances, accruals', category: 'Leave', icon: <Calendar size={20} /> },
    { name: 'Exit Register', desc: 'Off-boarded employees and reasons', category: 'HR', icon: <Users size={20} /> },
    { name: 'Audit Log', desc: 'Who did what, when', category: 'Compliance', icon: <BarChart3 size={20} /> },
  ]

  const categoryColors: Record<string, string> = {
    Statutory: 'bg-green-100 text-green-700',
    Payroll: 'bg-blue-100 text-blue-700',
    Attendance: 'bg-yellow-100 text-yellow-700',
    Finance: 'bg-purple-100 text-purple-700',
    Leave: 'bg-teal-100 text-teal-700',
    HR: 'bg-gray-100 text-gray-700',
    Compliance: 'bg-red-100 text-red-700',
  }

  return (
    <div className="space-y-5">
      <div className="bg-white border border-gray-100 rounded-xl p-5">
        <h3 className="font-700 text-gray-800 mb-1" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>HR & Payroll Reports</h3>
        <p className="text-xs text-gray-500 mb-4">Generate statutory returns, payroll registers, and compliance reports. All reports are filtered by month and year.</p>
        <div className="flex gap-3 mb-6">
          <select className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 bg-white">
            <option>July 2025</option>
            <option>June 2025</option>
            <option>May 2025</option>
          </select>
          <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Download size={16} /> Export All</button>
        </div>
      </div>

      <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {reports.map(report => (
          <div key={report.name} className="bg-white border border-gray-100 rounded-xl p-5 hover:shadow-md hover:border-green-200 transition-all group cursor-pointer">
            <div className="flex items-center justify-between mb-3">
              <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-700 group-hover:bg-green-200 transition-colors">
                {report.icon}
              </div>
              <span className={`text-[10px] font-medium px-2 py-0.5 rounded ${categoryColors[report.category]}`}>{report.category}</span>
            </div>
            <h4 className="font-700 text-sm text-gray-900 mb-1" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>{report.name}</h4>
            <p className="text-xs text-gray-500 mb-3">{report.desc}</p>
            <button className="text-xs text-green-700 font-semibold flex items-center gap-1 hover:gap-2 transition-all">Generate <ChevronRight size={14} /></button>
          </div>
        ))}
      </div>
    </div>
  )
}

function TrainingPage() {
  const trainings = [
    { id: 'TRN001', title: 'Workplace Safety & First Aid', date: '2025-08-15', trainer: 'Kenya Red Cross', status: 'upcoming', assigned: 25 },
    { id: 'TRN002', title: 'Anti-Harassment & Workplace Conduct', date: '2025-08-20', trainer: 'Internal HR', status: 'upcoming', assigned: 40 },
    { id: 'TRN003', title: 'Digital Health Records Training', date: '2025-07-10', trainer: 'Ministry of Health', status: 'completed', assigned: 15 },
    { id: 'TRN004', title: 'Community Health Outreach Skills', date: '2025-09-05', trainer: 'WHO Kenya', status: 'upcoming', assigned: 30 },
  ]

  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl p-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Training Programs</h3>
          <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><UserPlus size={16} /> Create Training</button>
        </div>
        <div className="space-y-3">
          {trainings.map(trn => (
            <div key={trn.id} className="flex items-center justify-between p-4 bg-gray-50 border border-gray-100 rounded-lg hover:shadow-sm transition-shadow">
              <div>
                <p className="text-sm font-600 text-gray-800">{trn.title}</p>
                <p className="text-xs text-gray-500">{trn.date} · {trn.trainer} · {trn.assigned} staff</p>
              </div>
              <span className={`badge ${trn.status === 'completed' ? 'badge-approved' : 'badge-pending'}`}>{trn.status}</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}

function AnalyticsPage() {
  const deptData = [
    { dept: 'Health Sciences', count: 5, avgSalary: 171000 },
    { dept: 'Catering & Hospitality', count: 2, avgSalary: 152500 },
    { dept: 'Business & Accounting', count: 1, avgSalary: 220000 },
    { dept: 'ICT', count: 1, avgSalary: 95000 },
    { dept: 'Data Science', count: 1, avgSalary: 145000 },
  ]

  return (
    <div className="space-y-5">
      <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard label="Total Headcount" value={String(EMPLOYEES.length)} sub="Across all departments" icon={<Users size={22} />} />
        <StatCard label="Avg Salary" value={`KES ${Math.round(EMPLOYEES.reduce((s, e) => s + e.salary, 0) / EMPLOYEES.length).toLocaleString()}`} sub="Monthly average" icon={<Wallet size={22} />} color="#1d4ed8" />
        <StatCard label="Turnover Rate" value="4.2%" sub="Annual" icon={<TrendingUp size={22} />} color="#dc2626" />
        <StatCard label="Training Hours" value="156" sub="This quarter" icon={<Award size={22} />} color="#15803d" />
      </div>

      <div className="bg-white border border-gray-100 rounded-xl p-5">
        <h3 className="font-700 text-gray-800 mb-4" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Headcount by Department</h3>
        <div className="space-y-3">
          {deptData.map(d => (
            <div key={d.dept}>
              <div className="flex items-center justify-between mb-1">
                <span className="text-xs font-500 text-gray-700">{d.dept}</span>
                <div className="flex items-center gap-3">
                  <span className="text-xs text-gray-500">{d.count} staff</span>
                  <span className="text-xs font-600 text-green-700">KES {d.avgSalary.toLocaleString()}</span>
                </div>
              </div>
              <div className="relative bg-gray-100 rounded-full h-2">
                <div className="h-full bg-green-500 rounded-full" style={{ width: `${(d.count / EMPLOYEES.length) * 100}%` }} />
              </div>
            </div>
          ))}
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

export function renderHRPages(subPage: 'overview' | 'employees' | 'onboarding' | 'leave' | 'attendance' | 'payroll' | 'expenses' | 'documents' | 'reports' | 'training' | 'analytics') {
  return <HRPages key={subPage} initialSubPage={subPage} />
}
