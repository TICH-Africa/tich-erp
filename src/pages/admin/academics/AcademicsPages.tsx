import { useState } from 'react'
import {
  APPLICATIONS, COURSES, EXAMS, EXAM_RESULTS, TIMETABLE, STUDENT_RECORDS,
  PROGRAMS, STAFF, DEPARTMENTS
} from '@/data/mock'
import {
  BarChart3, Users, GraduationCap, BookOpen, FileText,
  Search, ChevronRight, Plus, Download,
  Calendar, Award, CheckCircle, Building2, UserPlus, ClipboardList
} from 'lucide-react'

type AcademicsSubPage = 'overview' | 'students' | 'programs' | 'admissions' | 'staff' | 'examinations' | 'timetable' | 'departments' | 'records' | 'courses'

interface Props {
  initialSubPage?: AcademicsSubPage
}

export default function AcademicsPages({ initialSubPage = 'overview' }: Props) {
  const [activeSubPage, setActiveSubPage] = useState<AcademicsSubPage>(initialSubPage)

  const [studentSearch, setStudentSearch] = useState('')
  const [studentStatusFilter, setStudentStatusFilter] = useState('')
  const [programDeptFilter, setProgramDeptFilter] = useState('')
  const [programLevelFilter, setProgramLevelFilter] = useState('')
  const [appStatusFilter, setAppStatusFilter] = useState('')
  const [staffSearch, setStaffSearch] = useState('')
  const [staffDeptFilter, setStaffDeptFilter] = useState('')
  const [examStatusFilter, setExamStatusFilter] = useState('')
  const [examTypeFilter, setExamTypeFilter] = useState('')
  const [ttDeptFilter, setTtDeptFilter] = useState('')
  const [ttDayFilter, setTtDayFilter] = useState('')
  const [recordSearch, setRecordSearch] = useState('')
  const [recordStatusFilter, setRecordStatusFilter] = useState('')
  const [courseSearch, setCourseSearch] = useState('')
  const [courseDeptFilter, setCourseDeptFilter] = useState('')
  const [courseStatusFilter, setCourseStatusFilter] = useState('')

  const subNavItems: { label: string; page: AcademicsSubPage; icon: React.ReactNode }[] = [
    { label: 'Overview', page: 'overview', icon: <BarChart3 size={15} /> },
    { label: 'Students', page: 'students', icon: <Users size={15} /> },
    { label: 'Programs', page: 'programs', icon: <BookOpen size={15} /> },
    { label: 'Admissions', page: 'admissions', icon: <ClipboardList size={15} /> },
    { label: 'Staff', page: 'staff', icon: <GraduationCap size={15} /> },
    { label: 'Examinations', page: 'examinations', icon: <FileText size={15} /> },
    { label: 'Timetable', page: 'timetable', icon: <Calendar size={15} /> },
    { label: 'Departments', page: 'departments', icon: <Building2 size={15} /> },
    { label: 'Student Records', page: 'records', icon: <Award size={15} /> },
    { label: 'Courses', page: 'courses', icon: <BookOpen size={15} /> },
  ]

  const totalStudents = STUDENT_RECORDS.length
  const activeStudents = STUDENT_RECORDS.filter(s => s.status === 'active').length
  const totalCourses = COURSES.length
  const upcomingExams = EXAMS.filter(e => e.status === 'scheduled').length
  const passRate = EXAM_RESULTS.filter(r => r.status === 'pass').length / EXAM_RESULTS.length * 100

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

      {activeSubPage === 'overview' && (
        <div className="space-y-5">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <StatCard label="Total Students" value={String(totalStudents)} sub={`${activeStudents} active`} icon={<Users size={22} />} />
            <StatCard label="Total Courses" value={String(totalCourses)} sub="Across all departments" icon={<BookOpen size={22} />} color="#1d4ed8" />
            <StatCard label="Upcoming Exams" value={String(upcomingExams)} sub="Next 30 days" icon={<FileText size={22} />} color="#d97706" />
            <StatCard label="Pass Rate" value={`${passRate.toFixed(0)}%`} sub="Last semester" icon={<Award size={22} />} color="#15803d" />
          </div>
          <div className="grid md:grid-cols-2 gap-5">
            <div className="bg-white border border-gray-100 rounded-xl p-5">
              <h3 className="font-700 text-gray-800 mb-4" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Recent Admissions</h3>
              <div className="space-y-3">
                {APPLICATIONS.slice(0, 5).map(app => (
                  <div key={app.id} className="flex items-center justify-between p-3 bg-gray-50 border border-gray-100 rounded-lg">
                    <div>
                      <p className="text-xs font-600 text-gray-800">{app.studentName}</p>
                      <p className="text-[11px] text-gray-500">{app.program}</p>
                    </div>
                    <span className={`badge ${app.status === 'approved' ? 'badge-approved' : app.status === 'pending' ? 'badge-pending' : app.status === 'under_review' ? 'badge-review' : 'badge-rejected'}`}>{app.status}</span>
                  </div>
                ))}
              </div>
            </div>
            <div className="bg-white border border-gray-100 rounded-xl p-5">
              <h3 className="font-700 text-gray-800 mb-4" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Department Enrollment</h3>
              <div className="space-y-3">
                {PROGRAMS.reduce((acc, p) => {
                  const dept = p.department
                  if (!acc[dept]) acc[dept] = 0
                  acc[dept] += p.enrolled
                  return acc
                }, {} as Record<string, number>) && Object.entries(PROGRAMS.reduce((acc, p) => {
                  const dept = p.department
                  if (!acc[dept]) acc[dept] = 0
                  acc[dept] += p.enrolled
                  return acc
                }, {} as Record<string, number>)).map(([dept, count]) => (
                  <div key={dept}>
                    <div className="flex items-center justify-between mb-1">
                      <span className="text-xs font-500 text-gray-700">{dept}</span>
                      <span className="text-xs font-600 text-gray-800">{count} students</span>
                    </div>
                    <div className="relative bg-gray-100 rounded-full h-2">
                      <div className="h-full bg-green-500 rounded-full" style={{ width: `${(count / 1000) * 100}%` }} />
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}

      {activeSubPage === 'students' && (
        <div className="space-y-4">
          <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>All Students</h3>
              <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><UserPlus size={16} /> Add Student</button>
            </div>
            <div className="p-4 bg-gray-50 border-b border-gray-100">
              <div className="flex flex-col sm:flex-row gap-3">
                <div className="relative flex-1">
                  <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                  <input type="text" placeholder="Search by name, ID or program..." value={studentSearch} onChange={e => setStudentSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
                </div>
                <select value={studentStatusFilter} onChange={e => setStudentStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
                  <option value="">All Status</option>
                  <option value="active">Active</option>
                  <option value="probation">Probation</option>
                  <option value="suspended">Suspended</option>
                  <option value="graduated">Graduated</option>
                </select>
              </div>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead><tr className="border-b border-gray-100">
                  {['Student ID', 'Name', 'Program', 'Year', 'GPA', 'Status', 'Actions'].map(h => (
                    <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
                  ))}
                </tr></thead>
                <tbody>
                  {STUDENT_RECORDS.filter(s => {
                    const matchesSearch = s.studentName.toLowerCase().includes(studentSearch.toLowerCase()) || s.studentId.toLowerCase().includes(studentSearch.toLowerCase()) || s.program.toLowerCase().includes(studentSearch.toLowerCase())
                    const matchesStatus = studentStatusFilter === '' || s.status === studentStatusFilter
                    return matchesSearch && matchesStatus
                  }).map(s => (
                    <tr key={s.id} className="border-b border-gray-50 hover:bg-gray-50">
                      <td className="py-3 px-4 text-xs font-mono text-gray-500">{s.studentId}</td>
                      <td className="py-3 px-4 text-xs font-600 text-gray-800">{s.studentName}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{s.program}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">Year {s.year} · {s.semester}</td>
                      <td className="py-3 px-4 text-xs font-600 text-gray-700">{s.gpa.toFixed(1)}</td>
                      <td className="py-3 px-4"><span className={`badge ${s.status === 'active' ? 'badge-approved' : s.status === 'probation' ? 'badge-pending' : s.status === 'suspended' ? 'badge-rejected' : 'bg-gray-100 text-gray-600'}`}>{s.status}</span></td>
                      <td className="py-3 px-4"><button className="text-xs text-green-700 font-semibold flex items-center gap-1 hover:gap-2 transition-all">View <ChevronRight size={14} /></button></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {activeSubPage === 'programs' && (
        <div className="space-y-4">
          <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>All Programs</h3>
              <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> Add Program</button>
            </div>
            <div className="p-4 bg-gray-50 border-b border-gray-100">
              <div className="flex flex-col sm:flex-row gap-3">
                <div className="relative flex-1">
                  <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                  <input type="text" placeholder="Search programs..." value={courseSearch} onChange={e => setCourseSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
                </div>
                <select value={programDeptFilter} onChange={e => setProgramDeptFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
                  <option value="">All Departments</option>
                  {DEPARTMENTS.map(d => <option key={d} value={d}>{d}</option>)}
                </select>
                <select value={programLevelFilter} onChange={e => setProgramLevelFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
                  <option value="">All Levels</option>
                  {[...new Set(PROGRAMS.map(p => p.level))].map(l => <option key={l} value={l}>{l}</option>)}
                </select>
              </div>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead><tr className="border-b border-gray-100">
                  {['Code', 'Program Name', 'Department', 'Level', 'Duration', 'Enrolled', 'Fee', 'Status'].map(h => (
                    <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
                  ))}
                </tr></thead>
                <tbody>
                  {PROGRAMS.filter(p => {
                    const matchesSearch = p.name.toLowerCase().includes(courseSearch.toLowerCase()) || p.id.toLowerCase().includes(courseSearch.toLowerCase())
                    const matchesDept = programDeptFilter === '' || p.department === programDeptFilter
                    const matchesLevel = programLevelFilter === '' || p.level === programLevelFilter
                    return matchesSearch && matchesDept && matchesLevel
                  }).map(p => (
                    <tr key={p.id} className="border-b border-gray-50 hover:bg-gray-50">
                      <td className="py-3 px-4 text-xs font-mono text-gray-500">{p.id}</td>
                      <td className="py-3 px-4 text-xs font-600 text-gray-800">{p.name}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{p.department}</td>
                      <td className="py-3 px-4"><span className="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{p.level}</span></td>
                      <td className="py-3 px-4 text-xs text-gray-600">{p.duration}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{p.enrolled}/{p.capacity}</td>
                      <td className="py-3 px-4 text-xs font-600 text-gray-700">KES {p.fee.toLocaleString()}</td>
                      <td className="py-3 px-4"><span className="badge badge-approved">Active</span></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {activeSubPage === 'admissions' && (
        <div className="space-y-4">
          <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Applications</h3>
              <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> New Application</button>
            </div>
            <div className="p-4 bg-gray-50 border-b border-gray-100">
              <div className="flex flex-wrap gap-2">
                {['all', 'pending', 'under_review', 'approved', 'rejected'].map(status => (
                  <button key={status} onClick={() => setAppStatusFilter(status)} className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-colors ${appStatusFilter === status ? 'bg-green-100 text-green-700' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'}`}>
                    {status === 'all' ? 'All' : status.replace('_', ' ')}
                  </button>
                ))}
              </div>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead><tr className="border-b border-gray-100">
                  {['App ID', 'Student', 'Program', 'Applied', 'GPA', 'Nationality', 'Status', 'Actions'].map(h => (
                    <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
                  ))}
                </tr></thead>
                <tbody>
                  {APPLICATIONS.filter(app => appStatusFilter === 'all' || app.status === appStatusFilter).map(app => (
                    <tr key={app.id} className="border-b border-gray-50 hover:bg-gray-50">
                      <td className="py-3 px-4 text-xs font-mono text-gray-500">{app.id}</td>
                      <td className="py-3 px-4">
                        <p className="text-xs font-600 text-gray-800">{app.studentName}</p>
                        <p className="text-[10px] text-gray-400">{app.email}</p>
                      </td>
                      <td className="py-3 px-4 text-xs text-gray-600">{app.program}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{app.appliedDate}</td>
                      <td className="py-3 px-4 text-xs font-600 text-gray-700">{app.gpa ?? '—'}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{app.nationality}</td>
                      <td className="py-3 px-4"><span className={`badge ${app.status === 'approved' ? 'badge-approved' : app.status === 'pending' ? 'badge-pending' : app.status === 'under_review' ? 'badge-review' : 'badge-rejected'}`}>{app.status}</span></td>
                      <td className="py-3 px-4"><button className="text-xs text-green-700 font-semibold">Review</button></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {activeSubPage === 'staff' && (
        <div className="space-y-4">
          <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Academic Staff</h3>
              <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> Add Staff</button>
            </div>
            <div className="p-4 bg-gray-50 border-b border-gray-100">
              <div className="flex flex-col sm:flex-row gap-3">
                <div className="relative flex-1">
                  <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                  <input type="text" placeholder="Search staff..." value={staffSearch} onChange={e => setStaffSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
                </div>
                <select value={staffDeptFilter} onChange={e => setStaffDeptFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
                  <option value="">All Departments</option>
                  {[...new Set(STAFF.map(s => s.dept))].map(d => <option key={d} value={d}>{d}</option>)}
                </select>
              </div>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead><tr className="border-b border-gray-100">
                  {['ID', 'Name', 'Department', 'Role', 'Status', 'Actions'].map(h => (
                    <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
                  ))}
                </tr></thead>
                <tbody>
                  {STAFF.filter(s => {
                    const matchesSearch = s.name.toLowerCase().includes(staffSearch.toLowerCase()) || s.id.toLowerCase().includes(staffSearch.toLowerCase())
                    const matchesDept = staffDeptFilter === '' || s.dept === staffDeptFilter
                    return matchesSearch && matchesDept
                  }).map(s => (
                    <tr key={s.id} className="border-b border-gray-50 hover:bg-gray-50">
                      <td className="py-3 px-4 text-xs font-mono text-gray-500">{s.id}</td>
                      <td className="py-3 px-4 text-xs font-600 text-gray-800">{s.name}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{s.dept}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{s.role}</td>
                      <td className="py-3 px-4"><span className={`badge ${s.status === 'active' ? 'badge-approved' : s.status === 'probation' ? 'badge-pending' : 'badge-review'}`}>{s.status}</span></td>
                      <td className="py-3 px-4"><button className="text-xs text-green-700 font-semibold">View</button></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {activeSubPage === 'examinations' && (
        <div className="space-y-5">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <StatCard label="Total Exams" value={String(EXAMS.length)} sub="This semester" icon={<FileText size={22} />} />
            <StatCard label="Scheduled" value={String(EXAMS.filter(e => e.status === 'scheduled').length)} sub="Upcoming" icon={<Calendar size={22} />} color="#d97706" />
            <StatCard label="Completed" value={String(EXAMS.filter(e => e.status === 'completed').length)} sub="This semester" icon={<CheckCircle size={22} />} color="#15803d" />
            <StatCard label="Pass Rate" value={`${passRate.toFixed(0)}%`} sub="Last results" icon={<Award size={22} />} color="#1d4ed8" />
          </div>
          <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Examinations</h3>
              <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> Schedule Exam</button>
            </div>
            <div className="p-4 bg-gray-50 border-b border-gray-100">
              <div className="flex flex-wrap gap-2">
                {['all', 'scheduled', 'completed', 'postponed'].map(status => (
                  <button key={status} onClick={() => setExamStatusFilter(status)} className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-colors ${examStatusFilter === status ? 'bg-green-100 text-green-700' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'}`}>
                    {status === 'all' ? 'All' : status}
                  </button>
                ))}
                <select value={examTypeFilter} onChange={e => setExamTypeFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:border-green-500 bg-white">
                  <option value="">All Types</option>
                  {['CAT', 'Final', 'Supplementary'].map(t => <option key={t} value={t}>{t}</option>)}
                </select>
              </div>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead><tr className="border-b border-gray-100">
                  {['Exam ID', 'Course', 'Instructor', 'Date', 'Time', 'Venue', 'Type', 'Status'].map(h => (
                    <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
                  ))}
                </tr></thead>
                <tbody>
                  {EXAMS.filter(e => {
                    const matchesStatus = examStatusFilter === 'all' || e.status === examStatusFilter
                    const matchesType = examTypeFilter === '' || e.type === examTypeFilter
                    return matchesStatus && matchesType
                  }).map(exam => (
                    <tr key={exam.id} className="border-b border-gray-50 hover:bg-gray-50">
                      <td className="py-3 px-4 text-xs font-mono text-gray-500">{exam.id}</td>
                      <td className="py-3 px-4">
                        <p className="text-xs font-600 text-gray-800">{exam.courseName}</p>
                        <p className="text-[10px] text-gray-400">{exam.courseCode}</p>
                      </td>
                      <td className="py-3 px-4 text-xs text-gray-600">{exam.instructor}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{exam.date}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{exam.time}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{exam.venue}</td>
                      <td className="py-3 px-4"><span className="text-xs bg-green-50 text-green-700 px-2 py-0.5 rounded">{exam.type}</span></td>
                      <td className="py-3 px-4"><span className={`badge ${exam.status === 'scheduled' ? 'badge-pending' : exam.status === 'completed' ? 'badge-approved' : 'badge-review'}`}>{exam.status}</span></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {activeSubPage === 'timetable' && (
        <div className="space-y-4">
          <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Class Timetable</h3>
              <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> Add Entry</button>
            </div>
            <div className="p-4 bg-gray-50 border-b border-gray-100">
              <div className="flex flex-col sm:flex-row gap-3">
                <div className="relative flex-1">
                  <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                  <input type="text" placeholder="Search timetable..." value={ttDeptFilter} onChange={e => setTtDeptFilter(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
                </div>
                <select value={ttDayFilter} onChange={e => setTtDayFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
                  <option value="">All Days</option>
                  {['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'].map(d => <option key={d} value={d}>{d}</option>)}
                </select>
              </div>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead><tr className="border-b border-gray-100">
                  {['Course', 'Instructor', 'Day', 'Time', 'Venue', 'Department'].map(h => (
                    <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
                  ))}
                </tr></thead>
                <tbody>
                  {TIMETABLE.filter(entry => {
                    const matchesDept = ttDeptFilter === '' || entry.department.toLowerCase().includes(ttDeptFilter.toLowerCase()) || entry.courseName.toLowerCase().includes(ttDeptFilter.toLowerCase())
                    const matchesDay = ttDayFilter === '' || entry.day === ttDayFilter
                    return matchesDept && matchesDay
                  }).map(entry => (
                    <tr key={entry.id} className="border-b border-gray-50 hover:bg-gray-50">
                      <td className="py-3 px-4">
                        <p className="text-xs font-600 text-gray-800">{entry.courseName}</p>
                        <p className="text-[10px] text-gray-400">{entry.courseCode}</p>
                      </td>
                      <td className="py-3 px-4 text-xs text-gray-600">{entry.instructor}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{entry.day}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{entry.startTime} - {entry.endTime}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{entry.venue}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{entry.department}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {activeSubPage === 'departments' && (
        <div className="space-y-4">
          <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Departments</h3>
              <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> Add Department</button>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead><tr className="border-b border-gray-100">
                  {['Department', 'Head', 'Programs', 'Students', 'Status'].map(h => (
                    <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
                  ))}
                </tr></thead>
                <tbody>
                  {PROGRAMS.reduce((acc, p) => {
                    const dept = p.department
                    if (!acc.find(a => a.name === dept)) {
                      acc.push({ name: dept, head: 'TBD', programs: 0, students: 0 })
                    }
                    const deptObj = acc.find(a => a.name === dept)!
                    deptObj.programs += 1
                    deptObj.students += p.enrolled
                    return acc
                  }, [] as { name: string; head: string; programs: number; students: number }[]).map((dept, i) => (
                    <tr key={i} className="border-b border-gray-50 hover:bg-gray-50">
                      <td className="py-3 px-4 text-xs font-600 text-gray-800">{dept.name}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{dept.head}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{dept.programs}</td>
                      <td className="py-3 px-4 text-xs font-600 text-gray-700">{dept.students}</td>
                      <td className="py-3 px-4"><span className="badge badge-approved">Active</span></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {activeSubPage === 'records' && (
        <div className="space-y-4">
          <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Student Academic Records</h3>
              <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Download size={16} /> Export All</button>
            </div>
            <div className="p-4 bg-gray-50 border-b border-gray-100">
              <div className="flex flex-col sm:flex-row gap-3">
                <div className="relative flex-1">
                  <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                  <input type="text" placeholder="Search by name or ID..." value={recordSearch} onChange={e => setRecordSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
                </div>
                <select value={recordStatusFilter} onChange={e => setRecordStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
                  <option value="">All Status</option>
                  <option value="active">Active</option>
                  <option value="probation">Probation</option>
                  <option value="suspended">Suspended</option>
                  <option value="graduated">Graduated</option>
                </select>
              </div>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead><tr className="border-b border-gray-100">
                  {['Student ID', 'Name', 'Program', 'Year', 'Semester', 'GPA', 'Credits', 'Status', 'Actions'].map(h => (
                    <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
                  ))}
                </tr></thead>
                <tbody>
                  {STUDENT_RECORDS.filter(s => {
                    const matchesSearch = s.studentName.toLowerCase().includes(recordSearch.toLowerCase()) || s.studentId.toLowerCase().includes(recordSearch.toLowerCase())
                    const matchesStatus = recordStatusFilter === '' || s.status === recordStatusFilter
                    return matchesSearch && matchesStatus
                  }).map(s => (
                    <tr key={s.id} className="border-b border-gray-50 hover:bg-gray-50">
                      <td className="py-3 px-4 text-xs font-mono text-gray-500">{s.studentId}</td>
                      <td className="py-3 px-4 text-xs font-600 text-gray-800">{s.studentName}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{s.program}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">Year {s.year}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{s.semester}</td>
                      <td className="py-3 px-4 text-xs font-600 text-gray-700">{s.gpa.toFixed(1)}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{s.creditsCompleted}/{s.totalCredits}</td>
                      <td className="py-3 px-4"><span className={`badge ${s.status === 'active' ? 'badge-approved' : s.status === 'probation' ? 'badge-pending' : s.status === 'suspended' ? 'badge-rejected' : 'bg-gray-100 text-gray-600'}`}>{s.status}</span></td>
                      <td className="py-3 px-4"><button className="text-xs text-green-700 font-semibold flex items-center gap-1 hover:gap-2 transition-all">View <ChevronRight size={14} /></button></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {activeSubPage === 'courses' && (
        <div className="space-y-4">
          <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Courses</h3>
              <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> Add Course</button>
            </div>
            <div className="p-4 bg-gray-50 border-b border-gray-100">
              <div className="flex flex-col sm:flex-row gap-3">
                <div className="relative flex-1">
                  <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                  <input type="text" placeholder="Search courses..." value={courseSearch} onChange={e => setCourseSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
                </div>
                <select value={courseDeptFilter} onChange={e => setCourseDeptFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
                  <option value="">All Departments</option>
                  {[...new Set(COURSES.map(c => c.department))].map(d => <option key={d} value={d}>{d}</option>)}
                </select>
                <select value={courseStatusFilter} onChange={e => setCourseStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
                  <option value="">All Status</option>
                  {['active', 'upcoming', 'completed'].map(s => <option key={s} value={s}>{s}</option>)}
                </select>
              </div>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead><tr className="border-b border-gray-100">
                  {['Code', 'Course Name', 'Department', 'Instructor', 'Schedule', 'Enrolled', 'Credits', 'Fee', 'Status'].map(h => (
                    <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
                  ))}
                </tr></thead>
                <tbody>
                  {COURSES.filter(c => {
                    const matchesSearch = c.name.toLowerCase().includes(courseSearch.toLowerCase()) || c.code.toLowerCase().includes(courseSearch.toLowerCase())
                    const matchesDept = courseDeptFilter === '' || c.department === courseDeptFilter
                    const matchesStatus = courseStatusFilter === '' || c.status === courseStatusFilter
                    return matchesSearch && matchesDept && matchesStatus
                  }).map(course => (
                    <tr key={course.id} className="border-b border-gray-50 hover:bg-gray-50">
                      <td className="py-3 px-4 text-xs font-mono text-gray-500">{course.code}</td>
                      <td className="py-3 px-4 text-xs font-600 text-gray-800">{course.name}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{course.department}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{course.instructor}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{course.schedule}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{course.enrolled}/{course.capacity}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{course.credits}</td>
                      <td className="py-3 px-4 text-xs font-600 text-gray-700">KES {course.totalFee.toLocaleString()}</td>
                      <td className="py-3 px-4"><span className={`badge ${course.status === 'active' ? 'badge-approved' : course.status === 'upcoming' ? 'badge-pending' : 'bg-gray-100 text-gray-600'}`}>{course.status}</span></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

export function renderAcademicsPages(subPage: 'overview' | 'students' | 'programs' | 'admissions' | 'staff' | 'examinations' | 'timetable' | 'departments' | 'records' | 'courses') {
  return <AcademicsPages key={subPage} initialSubPage={subPage} />
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
