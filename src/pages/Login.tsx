import { useState } from 'react'
import logoImg from '@/imports/image.png'
import { DEMO_USERS, ROLES, type Role, type User } from '@/data/mock'
import { Eye, EyeOff, ChevronDown, ArrowLeft, Lock, Mail, ShoppingCart, Users } from 'lucide-react'

type SpecialRole = 'procurement' | 'sacco'

interface Props {
  onLogin: (user: User) => void
  onBack: () => void
  onModuleLogin?: (user: User, module: SpecialRole) => void
}

export default function Login({ onLogin, onBack, onModuleLogin }: Props) {
  const [selectedRole, setSelectedRole] = useState<Role | SpecialRole | ''>('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [showPass, setShowPass] = useState(false)
  const [showDropdown, setShowDropdown] = useState(false)
  const [error, setError] = useState('')

  const handleLogin = (e: React.FormEvent) => {
    e.preventDefault()
    if (!selectedRole) { setError('Please select your role.'); return }
    if (selectedRole === 'procurement' || selectedRole === 'sacco') {
      const module = selectedRole
      const users = module === 'procurement'
        ? DEMO_USERS.filter(u => ['super_admin', 'finance_manager', 'hod'].includes(u.role))
        : DEMO_USERS.filter(u => ['super_admin', 'finance_manager', 'hr_manager'].includes(u.role))
      const user = users[0]
      if (user && email === user.email) {
        onModuleLogin?.(user, module)
        return
      }
      setError('Use one of the demo emails shown in the dropdown.')
      return
    }
    const user = DEMO_USERS.find(u => u.role === selectedRole)
    if (user) {
      onLogin(user)
    } else {
      setError('No account found for this role.')
    }
  }

  const demoFill = (role: Role) => {
    const user = DEMO_USERS.find(u => u.role === role)
    if (user) { setEmail(user.email); setSelectedRole(role); setPassword('tich2026') }
    setShowDropdown(false)
  }

  const openModule = (module: SpecialRole) => {
    const users = module === 'procurement'
      ? DEMO_USERS.filter(u => ['super_admin', 'finance_manager', 'hod'].includes(u.role))
      : DEMO_USERS.filter(u => ['super_admin', 'finance_manager', 'hr_manager'].includes(u.role))
    const user = users[0]
    if (user) {
      setEmail(user.email)
      setPassword('tich2026')
      setSelectedRole(module)
    }
    setShowDropdown(false)
  }

  const moduleLabel = (module: SpecialRole) => module === 'procurement' ? 'Procurement & Logistics' : 'SACCO Portal'
  const moduleDesc = (module: SpecialRole) => module === 'procurement' ? 'Requisitions · Assets · Inventory' : 'Savings · Loans · Contributions'
  const moduleColor = (module: SpecialRole) => module === 'procurement' ? '#1d4ed8' : '#d97706'
  const moduleIcon = (module: SpecialRole) => module === 'procurement' ? <ShoppingCart size={18} /> : <Users size={18} />

  return (
    <div className="min-h-screen bg-gradient-to-br from-green-950 via-green-900 to-teal-900 flex items-center justify-center p-3">
      <div className="absolute inset-0 opacity-[0.06]"
        style={{ backgroundImage: 'radial-gradient(circle at 2px 2px, white 1px, transparent 0)', backgroundSize: '28px 28px' }} />

      <div className="relative w-full max-w-[340px]">
        <button onClick={onBack} className="flex items-center gap-2 text-green-300 text-[11px] mb-2 hover:text-white transition-colors">
          <ArrowLeft size={12} /> Back to Website
        </button>

        <div className="bg-white rounded-xl shadow-2xl overflow-hidden">
          {/* Header */}
          <div className="bg-gradient-to-r from-green-800 to-green-700 px-5 py-3 text-white text-center">
            <div className="flex justify-center mb-1.5">
              <div className="bg-white rounded-full p-1 shadow-md">
                <img src={logoImg} alt="TICH Logo" className="h-9 w-9 object-contain" />
              </div>
            </div>
            <h1 className="text-base font-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>TICH Staff Portal</h1>
            <p className="text-green-200 text-[10px] mt-0.5">Enterprise Resource Planning System</p>
          </div>

          {/* Form */}
          <div className="px-5 py-3">
            <h2 className="text-sm font-700 text-gray-900 mb-0.5" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Sign in to your account</h2>
            <p className="text-[11px] text-gray-500 mb-2">Select your role and enter your credentials.</p>

            {error && (
              <div className="bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg px-3 py-2 mb-3">{error}</div>
            )}

            <form onSubmit={handleLogin} className="space-y-2">
              {/* Role selector */}
              <div>
                <label className="block text-xs font-600 text-gray-600 mb-1.5">Select Your Role</label>
                <div className="relative">
                    <button type="button" onClick={() => setShowDropdown(!showDropdown)}
                      className="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm text-left flex items-center justify-between hover:border-green-400 transition-colors focus:outline-none focus:ring-2 focus:ring-green-200">
                    {selectedRole === 'procurement' || selectedRole === 'sacco'
                      ? <span className="flex items-center gap-2">
                          <span className="w-2 h-2 rounded-full" style={{ background: moduleColor(selectedRole) }} />
                          {moduleLabel(selectedRole)}
                        </span>
                      : selectedRole
                        ? <span className="flex items-center gap-2">
                            <span className="w-2 h-2 rounded-full" style={{ background: ROLES[selectedRole as Role].color }} />
                            {ROLES[selectedRole as Role].label}
                          </span>
                        : <span className="text-gray-400">— Choose your role —</span>
                    }
                    <ChevronDown size={14} className={`text-gray-400 transition-transform ${showDropdown ? 'rotate-180' : ''}`} />
                  </button>
                  {showDropdown && (
                    <div className="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-50 overflow-hidden">
                      <div className="py-1 max-h-48 overflow-y-auto">
                        {(Object.entries(ROLES) as [Role, typeof ROLES[Role]][]).map(([key, { label, color, description }]) => (
                          <button key={key} type="button" onClick={() => demoFill(key)}
                            className="w-full px-4 py-2.5 text-left hover:bg-green-50 flex items-center gap-3 transition-colors">
                            <span className="w-2.5 h-2.5 rounded-full flex-shrink-0" style={{ background: color }} />
                            <div>
                              <p className="text-sm font-500 text-gray-800">{label}</p>
                              <p className="text-xs text-gray-400">{description}</p>
                            </div>
                          </button>
                        ))}

                        <div className="border-t border-gray-100 my-1" />

                        {(['procurement', 'sacco'] as SpecialRole[]).map(module => (
                          <button key={module} type="button" onClick={() => openModule(module)}
                            className="w-full px-4 py-2.5 text-left hover:bg-blue-50 flex items-center gap-3 transition-colors">
                            <span className="w-2.5 h-2.5 rounded-full flex-shrink-0" style={{ background: moduleColor(module) }} />
                            <div>
                              <p className="text-sm font-500 text-gray-800 flex items-center gap-1.5">
                                {moduleIcon(module)}
                                {moduleLabel(module)}
                              </p>
                              <p className="text-xs text-gray-400">{moduleDesc(module)}</p>
                            </div>
                          </button>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              </div>

              {/* Email */}
              <div>
                <label className="block text-[11px] font-600 text-gray-600 mb-1">Institutional Email</label>
                <div className="relative">
                  <Mail size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                  <input type="email" value={email} onChange={e => setEmail(e.target.value)}
                     placeholder="yourname@tich.or.ke"
                    className="w-full border border-gray-200 rounded-lg pl-9 pr-3 py-1.5 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
                </div>
              </div>

              {/* Password */}
              <div>
                <label className="block text-[11px] font-600 text-gray-600 mb-1">Password</label>
                <div className="relative">
                  <Lock size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                  <input type={showPass ? 'text' : 'password'} value={password} onChange={e => setPassword(e.target.value)}
                    placeholder="••••••••"
                    className="w-full border border-gray-200 rounded-lg pl-9 pr-10 py-1.5 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
                  <button type="button" onClick={() => setShowPass(!showPass)} className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    {showPass ? <EyeOff size={14} /> : <Eye size={14} />}
                  </button>
                </div>
                <div className="flex justify-end mt-1">
                  <a href="#" className="text-xs text-green-600 hover:underline">Forgot password?</a>
                </div>
              </div>

              <button type="submit" className="btn-primary w-full py-2 text-xs mt-1">
                {selectedRole === 'procurement' ? 'Open Procurement Portal' : selectedRole === 'sacco' ? 'Open SACCO Portal' : 'Sign In to ERP'}
              </button>
            </form>

            <div className="mt-3 pt-3 border-t border-gray-100">
              <p className="text-[10px] text-gray-400 text-center mb-1.5">Access Main ERP</p>
              <div className="grid grid-cols-2 gap-1.5">
                <button type="button" onClick={() => demoFill('super_admin')}
                  className="text-[11px] border border-purple-200 rounded-lg px-2 py-1.5 hover:bg-purple-50 hover:border-purple-300 text-gray-700 transition-colors flex items-center gap-1 font-medium">
                  <span className="w-1.5 h-1.5 rounded-full" style={{ background: ROLES['super_admin'].color }} />
                  Super Admin
                </button>
                <button type="button" onClick={() => demoFill('hod')}
                  className="text-[11px] border border-blue-200 rounded-lg px-2 py-1.5 hover:bg-blue-50 hover:border-blue-300 text-gray-700 transition-colors flex items-center gap-1 font-medium">
                  <span className="w-1.5 h-1.5 rounded-full" style={{ background: ROLES['hod'].color }} />
                  HOD
                </button>
                <button type="button" onClick={() => demoFill('hr_manager')}
                  className="text-[11px] border border-amber-200 rounded-lg px-2 py-1.5 hover:bg-amber-50 hover:border-amber-300 text-gray-700 transition-colors flex items-center gap-1 font-medium">
                  <span className="w-1.5 h-1.5 rounded-full" style={{ background: ROLES['hr_manager'].color }} />
                  HR Manager
                </button>
                <button type="button" onClick={() => demoFill('finance_manager')}
                  className="text-[11px] border border-emerald-200 rounded-lg px-2 py-1.5 hover:bg-emerald-50 hover:border-emerald-300 text-gray-700 transition-colors flex items-center gap-1 font-medium">
                  <span className="w-1.5 h-1.5 rounded-full" style={{ background: ROLES['finance_manager'].color }} />
                  Finance Manager
                </button>
              </div>
            </div>
          </div>
        </div>

            <p className="text-center text-green-400 text-[10px] mt-2">
          © 2026 TICH ERP System · v1.2 · Authorised Access Only
        </p>
      </div>
    </div>
  )
}
