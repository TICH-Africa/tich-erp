import { useState } from 'react'
import { DEPARTMENTS, PROGRAMS } from '@/data/mock'
import { Search, Filter, BookOpen, ChevronRight, Eye, Send } from 'lucide-react'

interface Props {
  onBack: () => void
}

export default function ProgramsPage({ onBack }: Props) {
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
    <div className="min-h-screen bg-white" style={{ fontFamily: 'var(--font-body)' }}>
      <nav className="sticky top-0 z-50 bg-white border-b border-gray-100 shadow-sm">
        <div className="max-w-7xl mx-auto px-6 flex items-center justify-between h-16">
          <div className="flex items-center gap-3">
            <img src="/image.png" alt="TICH Logo" className="h-10 w-10 object-contain" />
            <div>
              <p className="text-sm font-800 leading-tight text-green-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>TICH</p>
              <p className="text-[10px] text-gray-500 leading-tight">Tropical Institute of Community Health and Development</p>
            </div>
          </div>
          <div className="hidden md:flex items-center gap-7">
            {['About', 'Events', 'Research', 'Blog', 'Contact'].map(item => (
              <a key={item} href={`#${item.toLowerCase()}`} className="text-sm text-gray-600 hover:text-green-700 font-medium transition-colors">{item}</a>
            ))}
          </div>
          <div className="flex items-center gap-3">
            <button onClick={onBack} className="text-sm text-green-700 font-semibold hover:underline">Back to Home</button>
          </div>
        </div>
      </nav>

      <section className="py-12 bg-gray-50">
        <div className="max-w-7xl mx-auto px-6">
          <div className="text-center mb-8">
            <span className="text-green-600 font-600 text-sm uppercase tracking-wider">Academic Offerings</span>
            <h2 className="text-3xl md:text-4xl font-800 mt-2 text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>
              All Programs
            </h2>
            <p className="text-gray-500 mt-3 max-w-xl mx-auto">Browse all {PROGRAMS.length} programs across our departments. Filter by department or search by name, level, or qualification.</p>
          </div>

          <div className="flex flex-col sm:flex-row gap-3 mb-8">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Search programs by name, level or qualification..."
                value={searchQuery}
                onChange={e => setSearchQuery(e.target.value)}
                className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100"
              />
            </div>
            <div className="relative">
              <Filter size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <select
                value={deptFilter}
                onChange={e => setDeptFilter(e.target.value)}
                className="appearance-none border border-gray-200 rounded-lg pl-10 pr-8 py-2.5 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white"
              >
                <option value="">All Departments</option>
                {DEPARTMENTS.map(d => (
                  <option key={d} value={d}>{d}</option>
                ))}
              </select>
            </div>
          </div>

          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            {filtered.map(p => (
              <div key={p.id} className="bg-white border border-gray-100 rounded-xl p-5 hover:shadow-md hover:border-green-200 transition-all group">
                <div className="flex items-start justify-between mb-3">
                  <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-700">
                    <BookOpen size={20} />
                  </div>
                  <span className="text-xs bg-green-50 text-green-700 px-2 py-0.5 rounded-full font-medium">{p.duration}</span>
                </div>
                <h3 className="font-700 text-gray-900 mb-1 group-hover:text-green-700 transition-colors" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>{p.name}</h3>
                <p className="text-xs text-gray-500 mb-1">{p.level}</p>
                <p className="text-xs text-gray-500 mb-3">{p.department}</p>
                <div className="flex items-center justify-between text-sm">
                  <span className="text-gray-500">{p.enrolled} enrolled</span>
                  <span className="font-600 text-green-700">KES {p.fee.toLocaleString()}{p.feeNote ? ` (${p.feeNote})` : '/sem'}</span>
                </div>
                <div className="mt-3 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                  <div className="h-full bg-green-500 rounded-full" style={{ width: `${Math.round((p.enrolled / p.capacity) * 100)}%` }} />
                </div>
                <p className="text-[11px] text-gray-400 mt-1">{Math.round((p.enrolled / p.capacity) * 100)}% capacity · {p.qualification}</p>
                <div className="mt-4 flex gap-2">
                  <button className="flex-1 flex items-center justify-center gap-1.5 border border-green-200 text-green-700 rounded-lg px-3 py-2 text-xs font-600 hover:bg-green-50 transition-colors">
                    <Eye size={14} /> View Details
                  </button>
                  <button className="flex-1 flex items-center justify-center gap-1.5 bg-green-700 text-white rounded-lg px-3 py-2 text-xs font-600 hover:bg-green-800 transition-colors">
                    <Send size={14} /> Apply Now
                  </button>
                </div>
              </div>
            ))}
          </div>

          {filtered.length === 0 && (
            <p className="text-center text-gray-400 text-sm mt-8">No programs match your search criteria.</p>
          )}

          <div className="text-center mt-10">
            <button onClick={onBack} className="btn-primary inline-flex items-center gap-2 px-8 py-3 text-sm">
              <ChevronRight size={16} className="rotate-180" /> Back to Home
            </button>
          </div>
        </div>
      </section>
    </div>
  )
}
