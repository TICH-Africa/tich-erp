import { useState } from 'react'
import logoImg from '@/imports/image.png'
import { DEPARTMENTS, PROGRAMS } from '@/data/mock'
import { Search, Filter, BookOpen, ChevronRight, Eye, Send, ChevronDown, Menu, X } from 'lucide-react'

interface Props {
  onBack: () => void
}

type NavSubItem = { label: string; href: string }
type NavGroup = { label: string; key: string; items: NavSubItem[] }

export default function ProgramsPage({ onBack }: Props) {
  const [deptFilter, setDeptFilter] = useState('')
  const [searchQuery, setSearchQuery] = useState('')
  const [openMenu, setOpenMenu] = useState<string | null>(null)
  const [mobileOpen, setMobileOpen] = useState(false)

  const navGroups: NavGroup[] = [
    {
      label: 'News & Events',
      key: 'news-events',
      items: [
        { label: 'Conference', href: '#conference' },
        { label: 'Events', href: '#events' },
        { label: 'Gallery', href: '#gallery' },
        { label: 'Blog', href: '#blog' },
      ]
    },
    {
      label: 'Admissions',
      key: 'admissions',
      items: [
        { label: 'HEF Application', href: '#hef' },
        { label: 'Financial Aid', href: '#financial-aid' },
        { label: 'TVETA Application', href: '#tveta' },
        { label: 'KUCCPS Application', href: '#kuccps' },
      ]
    },
    {
      label: 'About Us',
      key: 'about-us',
      items: [
        { label: 'About', href: '#about' },
        { label: 'Mission & Vision', href: '#mission' },
        { label: 'History', href: '#history' },
      ]
    },
    {
      label: 'Careers',
      key: 'careers',
      items: [
        { label: 'Talent Pool', href: '#talent-pool' },
        { label: 'Careers', href: '#careers' },
      ]
    },
  ]

  const standaloneItems = [
    { label: 'Research', href: '#research' },
    { label: 'Programs', href: '#programs' },
    { label: 'Contact', href: '#contact' },
  ]

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
      <nav className="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100">
        <div className="max-w-7xl mx-auto px-6 flex items-center justify-between h-16">
          <div className="flex items-center gap-3">
            <img src={logoImg} alt="TICH Logo" className="h-10 w-10 object-contain" />
            <div>
              <p className="text-sm font-extrabold leading-tight text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>TICH</p>
              <p className="text-[10px] text-gray-500 leading-tight">Tropical Institute of Community Health and Development</p>
            </div>
          </div>

          <div className="hidden lg:flex items-center gap-1">
            {navGroups.map(group => (
              <div key={group.key} className="relative"
                onMouseEnter={() => setOpenMenu(group.key)}
                onMouseLeave={() => setOpenMenu(null)}>
                <button className="flex items-center gap-1 px-3 py-2 text-xs font-semibold text-gray-600 hover:text-green-700 rounded-lg hover:bg-green-50 transition-all">
                  {group.label}
                  <ChevronDown size={12} className={`transition-transform duration-200 ${openMenu === group.key ? 'rotate-180' : ''}`} />
                </button>
                {openMenu === group.key && (
                  <div className="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-52 bg-white border border-gray-100 rounded-xl z-50 overflow-hidden">
                    <div className="p-1.5">
                      {group.items.map(item => (
                        <a key={item.href} href={item.href} className="block px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">
                          {item.label}
                        </a>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            ))}
            {standaloneItems.map(item => (
              <a key={item.href} href={item.href} className="px-3 py-2 text-xs font-semibold text-gray-600 hover:text-green-700 rounded-lg hover:bg-green-50 transition-all">
                {item.label}
              </a>
            ))}
          </div>

          <div className="hidden md:flex items-center gap-2">
            <div className="relative">
              <button onClick={() => setOpenMenu(openMenu === 'login' ? null : 'login')} className="px-3 py-1.5 text-xs font-semibold text-gray-600 hover:text-green-700 rounded-lg hover:bg-gray-50 transition-all flex items-center gap-1">
                Login
                <ChevronDown size={12} className={`transition-transform duration-200 ${openMenu === 'login' ? 'rotate-180' : ''}`} />
              </button>
              {openMenu === 'login' && (
                <div className="absolute top-full right-0 mt-2 w-40 bg-white border border-gray-100 rounded-xl z-50 overflow-hidden">
                  <div className="p-1.5">
                    <button onClick={onBack} className="block w-full text-left px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">
                      Login as Staff
                    </button>
                    <button onClick={onBack} className="block w-full text-left px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">
                      Login as Student
                    </button>
                  </div>
                </div>
              )}
            </div>
            <button onClick={onBack} className="px-5 py-2 text-xs font-bold text-white bg-green-700 hover:bg-green-800 rounded-xl shadow-xl shadow-green-700/30 hover:shadow-2xl hover:shadow-green-700/40 hover:-translate-y-0.5 transition-all">
              Apply Now
            </button>
          </div>

          <button onClick={() => setMobileOpen(!mobileOpen)} className="lg:hidden p-2 text-gray-600 hover:text-green-700">
            {mobileOpen ? <X size={24} /> : <Menu size={24} />}
          </button>
        </div>

        {mobileOpen && (
          <div className="lg:hidden border-t border-gray-100 bg-white/95 backdrop-blur-md">
            <div className="px-6 py-4 space-y-4">
              {navGroups.map(group => (
                <div key={group.key}>
                  <button onClick={() => setOpenMenu(openMenu === group.key ? null : group.key)} className="flex items-center justify-between w-full text-xs font-semibold text-gray-600 hover:text-green-700 py-1">
                    {group.label}
                    <ChevronDown size={12} className={`transition-transform duration-200 ${openMenu === group.key ? 'rotate-180' : ''}`} />
                  </button>
                  {openMenu === group.key && (
                    <div className="mt-1 ml-2 space-y-1">
                      {group.items.map(item => (
                        <a key={item.href} href={item.href} className="block px-3 py-2 text-xs text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">
                          {item.label}
                        </a>
                      ))}
                    </div>
                  )}
                </div>
              ))}
              {standaloneItems.map(item => (
                <a key={item.href} href={item.href} className="block text-xs font-semibold text-gray-600 hover:text-green-700 py-1">
                  {item.label}
                </a>
              ))}
              <div className="pt-3 border-t border-gray-100 space-y-2">
                <button onClick={onBack} className="block w-full text-left px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">
                  Login as Staff
                </button>
                <button onClick={onBack} className="block w-full text-left px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">
                  Login as Student
                </button>
                <button onClick={onBack} className="w-full text-center px-4 py-2.5 text-xs font-bold text-white bg-green-700 hover:bg-green-800 rounded-lg">
                  Apply Now
                </button>
              </div>
            </div>
          </div>
        )}
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
