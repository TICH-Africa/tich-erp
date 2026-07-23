import { useState } from 'react'
import logoImg from '@/imports/image.png'
import { BLOG_POSTS, FEATURED_PROGRAMS, INSTITUTIONAL_GOALS, RESEARCH_HIGHLIGHTS, UPCOMING_EVENTS } from '@/data/mock'
import { BookOpen, Briefcase, ChevronDown, ChevronRight, Eye, Globe, GraduationCap, MapPin, Mail, Phone, Send, Star, Users, Calendar, Clock, MapPinIcon, Award, Menu, X } from 'lucide-react'

interface Props {
  onLogin: () => void
  onViewAllPrograms: () => void
}

type NavSubItem = { label: string; href: string }
type NavGroup = { label: string; key: string; items: NavSubItem[] }

export default function Landing({ onLogin, onViewAllPrograms }: Props) {
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

  return (
    <div className="min-h-screen bg-white" style={{ fontFamily: 'var(--font-body)' }}>
      {/* ── NAVBAR ── */}
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
                    <button onClick={onLogin} className="block w-full text-left px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">
                      Login as Staff
                    </button>
                    <button onClick={onLogin} className="block w-full text-left px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">
                      Login as Student
                    </button>
                  </div>
                </div>
              )}
            </div>
            <button onClick={onViewAllPrograms} className="px-5 py-2 text-xs font-bold text-white bg-green-700 hover:bg-green-800 rounded-xl shadow-xl shadow-green-700/30 hover:shadow-2xl hover:shadow-green-700/40 hover:-translate-y-0.5 transition-all">
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
                <button onClick={onLogin} className="block w-full text-left px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">
                  Login as Staff
                </button>
                <button onClick={onLogin} className="block w-full text-left px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">
                  Login as Student
                </button>
                <button onClick={onViewAllPrograms} className="w-full text-center px-4 py-2.5 text-xs font-bold text-white bg-green-700 hover:bg-green-800 rounded-lg">
                  Apply Now
                </button>
              </div>
            </div>
          </div>
        )}
      </nav>

      {/* ── HERO ── */}
      <section className="relative overflow-hidden bg-gradient-to-br from-green-900 via-green-800 to-teal-900 text-white">
        <div className="absolute inset-0 opacity-10"
          style={{ backgroundImage: 'radial-gradient(circle at 2px 2px, rgba(255,255,255,0.4) 1px, transparent 0)', backgroundSize: '32px 32px' }} />
        <div className="absolute top-0 right-0 w-[600px] h-[600px] bg-green-500 opacity-10 rounded-full blur-3xl translate-x-1/3 -translate-y-1/3" />
        <div className="max-w-7xl mx-auto px-6 py-24 md:py-32 grid md:grid-cols-2 gap-12 items-center">
          <div>
            <span className="inline-block bg-green-500/20 text-green-300 text-xs font-semibold px-3 py-1 rounded-full mb-4 border border-green-500/30">
              Est. 2004 · Nairobi, Kenya
            </span>
            <h1 className="text-4xl md:text-5xl lg:text-6xl font-800 leading-tight mb-6" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>
              Shaping the Future of <span className="text-green-300">Community Health</span> Excellence
            </h1>
            <p className="text-lg text-green-100 leading-relaxed mb-8 max-w-lg">
              Kenya's premier institution for community health, development, and clinical training. Producing compassionate health professionals since 2004.
            </p>
            <div className="flex flex-wrap gap-3">
              <button onClick={onLogin} className="bg-white text-green-800 font-700 px-6 py-3 rounded-lg hover:bg-green-50 transition-colors" style={{ fontWeight: 700 }}>
                Apply for September 2026
              </button>
              <button className="border border-white/40 text-white font-500 px-6 py-3 rounded-lg hover:bg-white/10 transition-colors flex items-center gap-2">
                Explore Programs <ChevronRight size={16} />
              </button>
            </div>
            <div className="mt-10 flex gap-8">
              {[['1,640+', 'Students Enrolled'], ['9', 'Academic Programs'], ['200+', 'Industry Partners'], ['21', 'Years of Excellence']].map(([val, label]) => (
                <div key={label}>
                  <p className="text-2xl font-800 text-white" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>{val}</p>
                  <p className="text-xs text-green-300 mt-0.5">{label}</p>
                </div>
              ))}
            </div>
          </div>
          <div className="hidden md:block relative">
            <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-8">
              <div className="flex items-center gap-3 mb-6">
                <img src={logoImg} alt="TICH Logo" className="h-14 w-14 object-contain" />
                <div>
                  <p className="font-700 text-lg" style={{ fontWeight: 700 }}>Tropical Institute of</p>
                  <p className="text-green-300 text-sm">Community Health and Development (TICH)</p>
                </div>
              </div>
              <div className="space-y-3">
                {['Community-Linked Curriculum', 'Modern Clinical Labs', 'County Government Partnerships', 'Career Placement Support', 'Scholarship Opportunities'].map(f => (
                  <div key={f} className="flex items-center gap-2 text-sm text-green-100">
                    <div className="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                      <svg className="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" /></svg>
                    </div>
                    {f}
                  </div>
                ))}
              </div>
              <div className="mt-6 pt-6 border-t border-white/20 grid grid-cols-3 gap-4 text-center">
                <div><p className="text-xl font-700" style={{ fontWeight: 700 }}>92%</p><p className="text-xs text-green-300">Graduate Employment</p></div>
                <div><p className="text-xl font-700" style={{ fontWeight: 700 }}>4.6★</p><p className="text-xs text-green-300">Student Rating</p></div>
                <div><p className="text-xl font-700" style={{ fontWeight: 700 }}>KRA</p><p className="text-xs text-green-300">Accredited</p></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ── ANNOUNCEMENT TICKER ── */}
      <div className="bg-green-700 text-white text-sm py-2 overflow-hidden">
        <div className="flex items-center gap-4 px-6">
          <span className="bg-white text-green-700 font-700 text-xs px-2 py-0.5 rounded flex-shrink-0" style={{ fontWeight: 700 }}>NEWS</span>
            <span className="text-green-100">Applications for September 2026 intake are NOW OPEN &nbsp;·&nbsp; Scholarship applications close July 31, 2026 &nbsp;·&nbsp; Annual Culinary Showcase – August 15, 2026 at TICH Main Campus</span>
        </div>
      </div>

      {/* ── ABOUT ── */}
      <section id="about" className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-16 items-center">
          <div>
            <span className="text-green-600 font-600 text-sm uppercase tracking-wider">About TICH</span>
            <h2 className="text-3xl md:text-4xl font-800 mt-2 mb-5 text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>
              Kenya's Premier Health Training Institution
            </h2>
            <p className="text-gray-600 leading-relaxed mb-4">
              Tropical Institute of Community Health and Development (TICH) was established in 2004 to meet the growing demand for professionally trained hospitality and tourism personnel in Kenya and the East African region.
            </p>
            <p className="text-gray-600 leading-relaxed mb-6">
              Accredited by the Kenya Institute of Mass Communication and Technical and Vocational Education, TICH offers internationally benchmarked programs at certificate, diploma, degree, and Artisan Programs levels.
            </p>
            <div className="grid grid-cols-2 gap-4">
              {[
                { icon: <GraduationCap size={20} />, label: 'KIM and TVET Accredited' },
                { icon: <Award size={20} />, label: 'Ministry of Health Certified' },
                { icon: <Briefcase size={20} />, label: '50+ County Hospital Partners' },
                { icon: <Globe size={20} />, label: 'WHO Collaborating Centre' },
              ].map(({ icon, label }) => (
                <div key={label} className="flex items-center gap-3 bg-green-50 rounded-lg p-3">
                  <div className="text-green-600">{icon}</div>
                  <span className="text-sm font-medium text-gray-700">{label}</span>
                </div>
              ))}
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            {[
              { color: 'bg-green-800', title: 'Our Mission', text: 'To provide quality education in community health and development, producing compassionate professionals who serve communities\.' },
              { color: 'bg-green-600', title: 'Our Vision', text: 'To be the leading centre of excellence in community health education in Kenya and Africa\.' },
              { color: 'bg-teal-700', title: 'Core Values', text: 'Integrity · Excellence · Innovation · Inclusivity · Service' },
              { color: 'bg-green-900', title: 'Accreditation', text: 'Fully accredited by KIM and TVETA and affiliated with WHO and international health education bodies\.' },
            ].map(({ color, title, text }) => (
              <div key={title} className={`${color} text-white rounded-xl p-5`}>
                <h4 className="font-700 mb-2" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>{title}</h4>
                <p className="text-sm opacity-90 leading-relaxed">{text}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ── INSTITUTIONAL GOALS ── */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-6">
          <div className="text-center mb-14">
            <span className="text-green-600 font-600 text-sm uppercase tracking-wider">Our Vision</span>
            <h2 className="text-3xl md:text-4xl font-800 mt-2 text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>
              Institutional Goals
            </h2>
            <p className="text-gray-500 mt-3 max-w-xl mx-auto">Driving excellence in community health education through strategic priorities that transform lives\.</p>
          </div>
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {INSTITUTIONAL_GOALS.map(goal => (
              <div key={goal.title} className="bg-gray-50 border border-gray-100 rounded-xl p-6 hover:shadow-md hover:border-green-200 transition-all">
                <div className="text-3xl mb-3">{goal.icon}</div>
                <h3 className="font-700 text-gray-900 mb-2" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>{goal.title}</h3>
                <p className="text-sm text-gray-600 leading-relaxed">{goal.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ── PROGRAMS ── */}
      <section id="programs" className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-6">
          <div className="text-center mb-12">
            <span className="text-green-600 font-600 text-sm uppercase tracking-wider">Academic Offerings</span>
            <h2 className="text-3xl md:text-4xl font-800 mt-2 text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>
              Our Programs
            </h2>
            <p className="text-gray-500 mt-3 max-w-xl mx-auto">From artisan certificates to diploma programs, TICH offers pathways for every stage of your health career.</p>
          </div>
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            {FEATURED_PROGRAMS.slice(0, 3).map(p => (
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
          <div className="text-center mt-8">
            <button onClick={onViewAllPrograms} className="btn-primary inline-block px-8 py-3 text-sm">View All Programs</button>
          </div>
        </div>
      </section>

      {/* ── STATS BANNER ── */}
      <section className="bg-green-800 text-white py-14">
        <div className="max-w-7xl mx-auto px-6">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            {[
              { icon: <Users size={28} />, value: '1,640+', label: 'Active Students' },
              { icon: <GraduationCap size={28} />, value: '12,500+', label: 'Alumni Network in Health Sector' },
              { icon: <Award size={28} />, value: '92%', label: 'Employment Rate' },
              { icon: <Star size={28} />, value: '21 Years', label: 'of Excellence' },
            ].map(({ icon, value, label }) => (
              <div key={label} className="flex flex-col items-center gap-2">
                <div className="text-green-300">{icon}</div>
                <p className="text-3xl font-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>{value}</p>
                <p className="text-green-200 text-sm">{label}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ── UPCOMING EVENTS ── */}
      <section id="events" className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-6">
          <div className="text-center mb-14">
            <span className="text-green-600 font-600 text-sm uppercase tracking-wider">What's Happening</span>
            <h2 className="text-3xl md:text-4xl font-800 mt-2 text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>
              Upcoming Events
            </h2>
            <p className="text-gray-500 mt-3 max-w-xl mx-auto">Join us for health camps, research symposiums, and career fairs designed to connect students with health institutions\.</p>
          </div>
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-5">
            {UPCOMING_EVENTS.map(event => (
              <div key={event.id} className="bg-gray-50 border border-gray-100 rounded-xl p-5 hover:shadow-md hover:border-green-200 transition-all">
                <div className="flex items-center gap-2 mb-3">
                  <span className="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">{event.category}</span>
                </div>
                <h3 className="font-700 text-gray-900 mb-3" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>{event.title}</h3>
                <div className="space-y-1.5 text-xs text-gray-600">
                  <div className="flex items-center gap-2">
                    <Calendar size={14} className="text-green-600" />
                    <span>{event.date}</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Clock size={14} className="text-green-600" />
                    <span>{event.time}</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <MapPinIcon size={14} className="text-green-600" />
                    <span>{event.location}</span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ── RESEARCH ── */}
      <section id="research" className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-6">
          <div className="flex items-end justify-between mb-12">
            <div>
              <span className="text-green-600 font-600 text-sm uppercase tracking-wider">Knowledge & Innovation</span>
              <h2 className="text-3xl md:text-4xl font-800 mt-1 text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>Our Research</h2>
            </div>
            <a href="#research" className="text-sm text-green-700 font-semibold flex items-center gap-1 hover:gap-2 transition-all">View All Research <ChevronRight size={16} /></a>
          </div>
          <div className="grid md:grid-cols-3 gap-6">
            {RESEARCH_HIGHLIGHTS.map(r => (
              <article key={r.id} className="bg-white border border-gray-100 rounded-xl overflow-hidden hover:shadow-md transition-shadow group">
                <div className="bg-green-50 h-36 flex items-center justify-center">
                  <img src={logoImg} alt="TICH" className="h-16 w-16 object-contain opacity-40" />
                </div>
                <div className="p-5">
                  <div className="flex items-center gap-2 mb-2">
                    <span className="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded font-medium">{r.category}</span>
                    <span className="text-xs text-gray-400">{r.date}</span>
                  </div>
                  <h3 className="font-700 text-sm text-gray-900 mb-2 line-clamp-2 group-hover:text-green-700 transition-colors" style={{ fontWeight: 700 }}>{r.title}</h3>
                  <p className="text-xs text-gray-500 leading-relaxed mb-3 line-clamp-3">{r.excerpt}</p>
                  <p className="text-xs text-gray-400">By {r.authors}</p>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>

      {/* ── LATEST ── */}
      <section id="blog" className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-6">
          <div className="flex items-end justify-between mb-10">
            <div>
              <span className="text-green-600 font-600 text-sm uppercase tracking-wider">From Our Blog</span>
              <h2 className="text-3xl font-800 mt-1 text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>Latest</h2>
            </div>
            <a href="#blog" className="text-sm text-green-700 font-semibold flex items-center gap-1 hover:gap-2 transition-all">More <ChevronRight size={16} /></a>
          </div>
          <div className="grid md:grid-cols-3 gap-6">
            {BLOG_POSTS.map(post => (
              <article key={post.id} className="border border-gray-100 rounded-xl overflow-hidden hover:shadow-md transition-shadow group">
                <div className="bg-green-50 h-36 flex items-center justify-center">
                  <img src={logoImg} alt="TICH" className="h-16 w-16 object-contain opacity-40" />
                </div>
                <div className="p-5">
                  <div className="flex items-center gap-2 mb-2">
                    <span className="text-xs text-gray-400">{post.date}</span>
                    <span className="text-xs text-gray-300">·</span>
                    <span className="text-xs text-gray-400">{post.readTime} read</span>
                  </div>
                  <h3 className="font-700 text-sm text-gray-900 mb-2 line-clamp-2 group-hover:text-green-700 transition-colors" style={{ fontWeight: 700 }}>{post.title}</h3>
                  <p className="text-xs text-gray-500 leading-relaxed line-clamp-3 mb-3">{post.excerpt}</p>
                  <p className="text-xs text-gray-400">By {post.author}</p>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>

      {/* ── ADMISSIONS CTA ── */}
      <section id="admissions" className="py-20 bg-gradient-to-r from-green-700 to-teal-700 text-white">
        <div className="max-w-4xl mx-auto px-6 text-center">
          <h2 className="text-3xl md:text-4xl font-800 mb-4" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>Ready to Begin Your Career in Health\?</h2>
           <p className="text-green-100 text-lg mb-8">Applications for the September 2026 intake are open. Scholarship opportunities available for qualified applicants.</p>
          <div className="flex flex-wrap justify-center gap-4">
            <button onClick={onLogin} className="bg-white text-green-800 font-700 px-8 py-3 rounded-lg hover:bg-green-50 transition-colors" style={{ fontWeight: 700 }}>Apply Online Now</button>
            <button className="border border-white/50 text-white px-8 py-3 rounded-lg hover:bg-white/10 transition-colors">Download Fee Structure</button>
          </div>
          <div className="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm">
            <div className="bg-white/10 backdrop-blur-sm rounded-xl p-4">
              <p className="font-600 text-green-200 text-xs uppercase mb-1">Application Deadline</p>
               <p className="font-700 text-lg" style={{ fontWeight: 700 }}>August 15, 2026</p>
            </div>
            <div className="bg-white/10 backdrop-blur-sm rounded-xl p-4">
              <p className="font-600 text-green-200 text-xs uppercase mb-1">Intake Date</p>
               <p className="font-700 text-lg" style={{ fontWeight: 700 }}>September 1, 2026</p>
            </div>
            <div className="bg-white/10 backdrop-blur-sm rounded-xl p-4">
              <p className="font-600 text-green-200 text-xs uppercase mb-1">Scholarships Available</p>
               <p className="font-700 text-lg" style={{ fontWeight: 700 }}>12 Full Grants</p>
            </div>
          </div>
        </div>
      </section>

      {/* ── CONTACT ── */}
      <section id="contact" className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-12">
          <div>
            <span className="text-green-600 font-600 text-sm uppercase tracking-wider">Get in Touch</span>
            <h2 className="text-3xl font-800 mt-2 mb-5 text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>Contact Us</h2>
            <div className="space-y-4">
              {[
                { icon: <MapPin size={18} />, label: 'Address', value: 'P.O. Box 12345, Nairobi, Kenya' },
                { icon: <Phone size={18} />, label: 'Phone', value: '+254 20 123 4567 / +254 711 222 333' },
                { icon: <Mail size={18} />, label: 'Email', value: 'info@tich.or.ke' },
                { icon: <Globe size={18} />, label: 'Website', value: 'www.tich.or.ke' },
              ].map(({ icon, label, value }) => (
                <div key={label} className="flex gap-3">
                  <div className="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center text-green-700 flex-shrink-0">{icon}</div>
                  <div>
                    <p className="text-xs text-gray-400 font-medium">{label}</p>
                    <p className="text-sm text-gray-800">{value}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
          <div className="bg-white border border-gray-100 rounded-xl p-6 shadow-sm">
            <h3 className="font-700 text-gray-900 mb-5" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Send us a Message</h3>
            <div className="space-y-4">
              {['Full Name', 'Email Address', 'Phone Number'].map(field => (
                <div key={field}>
                  <label className="block text-xs font-medium text-gray-600 mb-1">{field}</label>
                  <input type="text" placeholder={field} className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-200" />
                </div>
              ))}
              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">Message</label>
                <textarea rows={3} placeholder="Your message..." className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-200 resize-none" />
              </div>
              <button className="btn-primary w-full py-2.5">Send Message</button>
            </div>
          </div>
        </div>
      </section>

      {/* ── FOOTER ── */}
      <footer className="bg-green-950 text-white py-10">
        <div className="max-w-7xl mx-auto px-6">
          <div className="grid md:grid-cols-4 gap-8 mb-8">
            <div className="md:col-span-1">
              <div className="flex items-center gap-2 mb-3">
                <img src={logoImg} alt="TICH" className="h-10 w-10 object-contain" />
                <div>
                  <p className="font-700 text-sm" style={{ fontWeight: 700 }}>TICH</p>
                  <p className="text-green-400 text-xs">Tropical Institute of of Hospitality</p>
                </div>
              </div>
              <p className="text-xs text-green-300 leading-relaxed">Shaping community health professionals for Kenya and Africa since 2004\.</p>
            </div>
            {[
              { title: 'Programs', links: ['Health Programs', 'Clinical Programs', 'Certificate Programs', 'Artisan Programs'] },
              { title: 'Admissions', links: ['Admissions', 'Entry Requirements', 'Scholarships', 'Fee Structure'] },
              { title: 'Campus Life', links: ['Clinical Placements', 'Student Support', 'Research & Innovation', 'Alumni Network'] },
            ].map(({ title, links }) => (
              <div key={title}>
                <h4 className="font-700 text-sm mb-3 text-green-200" style={{ fontWeight: 700 }}>{title}</h4>
                <ul className="space-y-2">
                  {links.map(l => <li key={l}><a href="#" className="text-xs text-green-400 hover:text-white transition-colors">{l}</a></li>)}
                </ul>
              </div>
            ))}
          </div>
          <div className="border-t border-green-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-green-500">
            <p>© 2026 Tropical Institute of Community Health and Development (TICH). All rights reserved.</p>
            <div className="flex gap-4">
              <a href="#" className="hover:text-white transition-colors">Privacy Policy</a>
              <a href="#" className="hover:text-white transition-colors">Terms of Use</a>
              <button onClick={onLogin} className="hover:text-white transition-colors">Staff Portal</button>
            </div>
          </div>
        </div>
      </footer>
    </div>
  )
}
