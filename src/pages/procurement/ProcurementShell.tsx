import { useState } from 'react'
import {
  REQUISITIONS, SUPPLIERS, PURCHASE_ORDERS, PROCUREMENT_INVOICES, ASSETS, INVENTORY,
  type Requisition, type Supplier, type PurchaseOrder, type ProcurementInvoice, type Asset, type InventoryItem
} from '@/data/mock'
import {
  Search, Plus, ChevronRight, BarChart3, DollarSign,
  ClipboardList, Building2, Package, ShoppingCart, FileText, Warehouse,
  Bell, ChevronDown, Menu
} from 'lucide-react'

interface Props {
  user: { name: string; email: string; avatar: string; role?: string }
  onLogout: () => void
}

type ProcurementSubPage = 'overview' | 'requisitions' | 'suppliers' | 'orders' | 'invoices' | 'assets' | 'inventory'

export default function ProcurementShell({ user, onLogout }: Props) {
  const [activeSubPage, setActiveSubPage] = useState<ProcurementSubPage>('overview')
  const [notifOpen, setNotifOpen] = useState(false)
  const [userMenuOpen, setUserMenuOpen] = useState(false)
  const [mobileNavOpen, setMobileNavOpen] = useState(false)

  const subNavItems: { label: string; page: ProcurementSubPage; icon: React.ReactNode }[] = [
    { label: 'Overview', page: 'overview', icon: <BarChart3 size={15} /> },
    { label: 'Requisitions', page: 'requisitions', icon: <ClipboardList size={15} /> },
    { label: 'Suppliers', page: 'suppliers', icon: <Building2 size={15} /> },
    { label: 'Orders', page: 'orders', icon: <ShoppingCart size={15} /> },
    { label: 'Invoices', page: 'invoices', icon: <FileText size={15} /> },
    { label: 'Assets', page: 'assets', icon: <Package size={15} /> },
    { label: 'Inventory', page: 'inventory', icon: <Warehouse size={15} /> },
  ]

  return (
    <div className="flex h-screen bg-gray-50 overflow-hidden">
      {/* Mobile backdrop */}
      {mobileNavOpen && (
        <div className="fixed inset-0 bg-black/30 z-30 lg:hidden" onClick={() => setMobileNavOpen(false)} />
      )}
      <aside className={`w-60 flex-shrink-0 bg-white border-r border-gray-100 flex flex-col fixed inset-y-0 left-0 z-40 transform transition-transform duration-200 ${mobileNavOpen ? 'translate-x-0' : '-translate-x-full'} lg:relative lg:translate-x-0`}>
        <div className="flex items-center gap-3 px-4 py-4 border-b border-gray-100">
          <div className="w-8 h-8 rounded-full bg-blue-700 flex items-center justify-center text-white font-bold text-xs">P</div>
          <div>
            <p className="text-sm font-extrabold text-blue-800 leading-tight">Procurement</p>
            <p className="text-[10px] text-gray-400 leading-tight">TICH Portal</p>
          </div>
        </div>
        <div className="mx-3 mt-3 px-3 py-2 rounded-lg bg-blue-50">
          <p className="text-[11px] font-600 text-gray-500">Signed in as</p>
          <p className="text-xs font-700 mt-0.5 text-blue-700">{user.name}</p>
        </div>
        <nav className="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
          {subNavItems.map(({ label, page, icon }) => (
            <button key={page} onClick={() => setActiveSubPage(page)}
              className={`w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${activeSubPage === page ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'}`}>
              {icon}
              <span>{label}</span>
            </button>
          ))}
        </nav>
        <div className="p-2 border-t border-gray-100">
          <button onClick={onLogout} className="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-red-500 hover:bg-red-50 hover:text-red-600 transition-colors">
            Logout
          </button>
        </div>
      </aside>

      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
        <header className="h-14 bg-white border-b border-gray-100 flex items-center justify-between px-5 flex-shrink-0">
          <div className="flex items-center gap-3">
            <button onClick={() => setMobileNavOpen(true)} className="text-gray-400 hover:text-gray-700 transition-colors lg:hidden">
              <Menu size={20} />
            </button>
            <div>
              <p className="text-sm font-bold text-gray-800">{subNavItems.find(n => n.page === activeSubPage)?.label ?? 'Dashboard'}</p>
              <p className="text-xs text-gray-400">Procurement · {new Date().toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' })}</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <div className="relative">
              <button onClick={() => { setNotifOpen(!notifOpen); setUserMenuOpen(false) }} className="relative w-9 h-9 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-500 transition-colors">
                <Bell size={18} />
                <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full" />
              </button>
              {notifOpen && (
                <div className="absolute right-0 top-full mt-1 w-72 bg-white border border-gray-100 rounded-xl shadow-lg z-50 overflow-hidden">
                  <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <p className="text-sm font-600">Notifications</p>
                    <button onClick={() => setNotifOpen(false)}><span className="text-gray-400 text-xs">✕</span></button>
                  </div>
                  <div className="px-4 py-3 border-b border-gray-50">
                    <p className="text-xs text-gray-700">PO-002 awaiting delivery</p>
                    <p className="text-[10px] text-gray-400 mt-0.5">2 hours ago</p>
                  </div>
                  <div className="px-4 py-3 border-b border-gray-50">
                    <p className="text-xs text-gray-700">REQ-002 requires approval</p>
                    <p className="text-[10px] text-gray-400 mt-0.5">5 hours ago</p>
                  </div>
                </div>
              )}
            </div>
            <div className="relative">
              <button onClick={() => { setUserMenuOpen(!userMenuOpen); setNotifOpen(false) }} className="flex items-center gap-2 pl-2 pr-1 py-1 rounded-lg hover:bg-gray-100 transition-colors">
                <div className="w-8 h-8 rounded-full flex items-center justify-center text-xs font-700 text-white flex-shrink-0" style={{ background: '#1d4ed8', fontWeight: 700 }}>
                  {user.avatar}
                </div>
                <span className="text-xs font-600 text-gray-800 hidden sm:block">{user.name}</span>
                <ChevronDown size={14} className="text-gray-400" />
              </button>
              {userMenuOpen && (
                <div className="absolute right-0 top-full mt-1 w-52 bg-white border border-gray-100 rounded-xl shadow-lg z-50 overflow-hidden">
                  <div className="px-4 py-3 border-b border-gray-100">
                    <p className="text-xs font-600 text-gray-800">{user.name}</p>
                    <p className="text-[10px] text-gray-500">{user.email}</p>
                  </div>
                  <div className="py-1">
                    <button className="w-full px-4 py-2 text-xs text-left hover:bg-gray-50 text-gray-700">My Profile</button>
                    <button onClick={onLogout} className="w-full px-4 py-2 text-xs text-left hover:bg-red-50 text-red-600">Sign Out</button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </header>

        <main className="flex-1 overflow-y-auto p-5">
          {activeSubPage === 'overview' && <ProcurementOverview onNavigate={setActiveSubPage} />}
          {activeSubPage === 'requisitions' && <RequisitionsPage requisitions={REQUISITIONS} />}
          {activeSubPage === 'suppliers' && <SuppliersPage suppliers={SUPPLIERS} />}
          {activeSubPage === 'orders' && <OrdersPage orders={PURCHASE_ORDERS} />}
          {activeSubPage === 'invoices' && <InvoicesPage invoices={PROCUREMENT_INVOICES} />}
          {activeSubPage === 'assets' && <AssetsPage assets={ASSETS} />}
          {activeSubPage === 'inventory' && <InventoryPage items={INVENTORY} />}
        </main>
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

function ProcurementOverview({ onNavigate }: { onNavigate: (page: ProcurementSubPage) => void }) {
  const totalRequisitions = REQUISITIONS.length
  const pendingReqs = REQUISITIONS.filter(r => r.status === 'pending').length
  const totalSpend = PROCUREMENT_INVOICES.reduce((sum, inv) => sum + inv.amount, 0)
  const activeAssets = ASSETS.length

  return (
    <div className="space-y-5">
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <StatCard label="Total Requisitions" value={String(totalRequisitions)} sub={`${pendingReqs} pending`} icon={<ClipboardList size={22} />} />
        <StatCard label="Total Spend" value={`KES ${(totalSpend / 1000000).toFixed(1)}M`} sub="Procurement invoices" icon={<DollarSign size={22} />} color="#1d4ed8" />
        <StatCard label="Active Suppliers" value={String(SUPPLIERS.length)} sub="Approved vendors" icon={<Building2 size={22} />} color="#15803d" />
        <StatCard label="Assets Managed" value={String(activeAssets)} sub="On registry" icon={<Package size={22} />} color="#d97706" />
      </div>
      <div className="grid md:grid-cols-2 gap-5">
        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <h3 className="font-bold text-gray-800 mb-4">Recent Requisitions</h3>
          <div className="space-y-3">
            {REQUISITIONS.slice(0, 5).map(r => (
              <div key={r.id} className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                  <p className="text-xs font-semibold text-gray-800">{r.title}</p>
                  <p className="text-[11px] text-gray-400">{r.department} · {r.createdAt}</p>
                </div>
                <div className="text-right">
                  <p className="text-xs font-semibold text-gray-700">KES {r.budget.toLocaleString()}</p>
                  <span className={`badge badge-${r.status === 'approved' ? 'approved' : r.status === 'pending' ? 'pending' : 'review'}`}>{r.status}</span>
                </div>
              </div>
            ))}
          </div>
        </div>
        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <h3 className="font-bold text-gray-800 mb-4">Recent Purchase Orders</h3>
          <div className="space-y-3">
            {PURCHASE_ORDERS.map(po => (
              <div key={po.id} className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                  <p className="text-xs font-semibold text-gray-800">{po.id}</p>
                  <p className="text-[11px] text-gray-400">{po.supplierId} · {po.orderDate}</p>
                </div>
                <div className="text-right">
                  <p className="text-xs font-semibold text-gray-700">KES {po.amount.toLocaleString()}</p>
                  <span className={`badge badge-${po.status === 'delivered' ? 'approved' : po.status === 'sent' ? 'pending' : 'review'}`}>{po.status}</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">Assets Registry</h3>
          <button onClick={() => onNavigate('assets')} className="text-xs text-blue-700 font-semibold flex items-center gap-1 hover:gap-2 transition-all">View All <ChevronRight size={14} /></button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Asset ID', 'Name', 'Category', 'Department', 'Location', 'Status'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {ASSETS.slice(0, 5).map(asset => (
                <tr key={asset.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{asset.id}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-800">{asset.name}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{asset.category}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{asset.department}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{asset.location}</td>
                  <td className="py-3 px-4"><span className={`badge badge-${asset.status === 'active' ? 'approved' : asset.status === 'maintenance' ? 'pending' : 'rejected'}`}>{asset.status}</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function RequisitionsPage({ requisitions }: { requisitions: Requisition[] }) {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const filtered = requisitions.filter(r => {
    const matchesSearch = r.title.toLowerCase().includes(search.toLowerCase()) || r.department.toLowerCase().includes(search.toLowerCase())
    const matchesStatus = statusFilter === '' || r.status === statusFilter
    return matchesSearch && matchesStatus
  })
  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">Requisition Requests</h3>
          <button className="bg-blue-700 text-white flex items-center gap-2 px-4 py-2 rounded-lg text-sm hover:bg-blue-800"><Plus size={16} /> New Requisition</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search requisitions..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
              <option value="">All Status</option>
              <option value="draft">Draft</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
              <option value="ordered">Ordered</option>
              <option value="delivered">Delivered</option>
            </select>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Requisition ID', 'Title', 'Department', 'Requested By', 'Budget', 'Status', 'Date'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(r => (
                <tr key={r.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{r.id}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-800">{r.title}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{r.department}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{r.requestedBy}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-700">KES {r.budget.toLocaleString()}</td>
                  <td className="py-3 px-4"><span className={`badge badge-${r.status === 'approved' ? 'approved' : r.status === 'pending' ? 'pending' : 'review'}`}>{r.status}</span></td>
                  <td className="py-3 px-4 text-xs text-gray-600">{r.createdAt}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function SuppliersPage({ suppliers }: { suppliers: Supplier[] }) {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const filtered = suppliers.filter(s => {
    const matchesSearch = s.name.toLowerCase().includes(search.toLowerCase()) || s.category.toLowerCase().includes(search.toLowerCase())
    const matchesStatus = statusFilter === '' || s.status === statusFilter
    return matchesSearch && matchesStatus
  })
  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">Supplier Directory</h3>
          <button className="bg-blue-700 text-white flex items-center gap-2 px-4 py-2 rounded-lg text-sm hover:bg-blue-800"><Plus size={16} /> Add Supplier</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search suppliers..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="blacklisted">Blacklisted</option>
            </select>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Supplier ID', 'Name', 'Category', 'Email', 'Phone', 'Rating', 'Outstanding', 'Status'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(s => (
                <tr key={s.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{s.id}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-800">{s.name}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{s.category}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{s.email}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{s.phone}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{s.rating}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">KES {s.outstanding.toLocaleString()}</td>
                  <td className="py-3 px-4"><span className={`badge badge-${s.status === 'active' ? 'approved' : s.status === 'inactive' ? 'pending' : 'rejected'}`}>{s.status}</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function OrdersPage({ orders }: { orders: PurchaseOrder[] }) {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const filtered = orders.filter(o => {
    const matchesSearch = o.id.toLowerCase().includes(search.toLowerCase()) || o.supplierId.toLowerCase().includes(search.toLowerCase())
    const matchesStatus = statusFilter === '' || o.status === statusFilter
    return matchesSearch && matchesStatus
  })
  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">Purchase Orders</h3>
          <button className="bg-blue-700 text-white flex items-center gap-2 px-4 py-2 rounded-lg text-sm hover:bg-blue-800"><Plus size={16} /> New Order</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search orders..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
              <option value="">All Status</option>
              <option value="draft">Draft</option>
              <option value="sent">Sent</option>
              <option value="acknowledged">Acknowledged</option>
              <option value="delivered">Delivered</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['PO ID', 'Requisition', 'Supplier', 'Amount', 'Status', 'Order Date', 'Expected Delivery'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(o => (
                <tr key={o.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{o.id}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{o.requisitionId}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{o.supplierId}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-700">KES {o.amount.toLocaleString()}</td>
                  <td className="py-3 px-4"><span className={`badge badge-${o.status === 'delivered' ? 'approved' : o.status === 'sent' ? 'pending' : 'review'}`}>{o.status}</span></td>
                  <td className="py-3 px-4 text-xs text-gray-600">{o.orderDate}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{o.expectedDelivery}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function InvoicesPage({ invoices }: { invoices: ProcurementInvoice[] }) {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const filtered = invoices.filter(inv => {
    const matchesSearch = inv.id.toLowerCase().includes(search.toLowerCase()) || inv.purchaseOrderId.toLowerCase().includes(search.toLowerCase())
    const matchesStatus = statusFilter === '' || inv.status === statusFilter
    return matchesSearch && matchesStatus
  })
  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">Procurement Invoices</h3>
          <button className="bg-blue-700 text-white flex items-center gap-2 px-4 py-2 rounded-lg text-sm hover:bg-blue-800"><Plus size={16} /> New Invoice</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search invoices..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
              <option value="">All Status</option>
              <option value="pending">Pending</option>
              <option value="verified">Verified</option>
              <option value="approved">Approved</option>
              <option value="paid">Paid</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Invoice ID', 'PO ID', 'Supplier', 'Amount', 'Status', 'Invoice Date', 'Due Date'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(inv => (
                <tr key={inv.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{inv.id}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{inv.purchaseOrderId}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{inv.supplierId}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-700">KES {inv.amount.toLocaleString()}</td>
                  <td className="py-3 px-4"><span className={`badge badge-${inv.status === 'paid' ? 'approved' : inv.status === 'approved' ? 'approved' : inv.status === 'verified' ? 'review' : 'pending'}`}>{inv.status}</span></td>
                  <td className="py-3 px-4 text-xs text-gray-600">{inv.invoiceDate}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{inv.dueDate}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function AssetsPage({ assets }: { assets: Asset[] }) {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const filtered = assets.filter(a => {
    const matchesSearch = a.name.toLowerCase().includes(search.toLowerCase()) || a.category.toLowerCase().includes(search.toLowerCase()) || a.serialNumber.toLowerCase().includes(search.toLowerCase())
    const matchesStatus = statusFilter === '' || a.status === statusFilter
    return matchesSearch && matchesStatus
  })
  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">Asset Registry</h3>
          <button className="bg-blue-700 text-white flex items-center gap-2 px-4 py-2 rounded-lg text-sm hover:bg-blue-800"><Plus size={16} /> Add Asset</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search assets..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="maintenance">Maintenance</option>
              <option value="disposed">Disposed</option>
            </select>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Asset ID', 'Name', 'Category', 'Serial Number', 'Cost', 'Department', 'Location', 'Status'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(a => (
                <tr key={a.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{a.id}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-800">{a.name}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{a.category}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{a.serialNumber}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-700">KES {a.cost.toLocaleString()}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{a.department}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{a.location}</td>
                  <td className="py-3 px-4"><span className={`badge badge-${a.status === 'active' ? 'approved' : a.status === 'maintenance' ? 'pending' : 'rejected'}`}>{a.status}</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function InventoryPage({ items }: { items: InventoryItem[] }) {
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const filtered = items.filter(item => {
    const matchesSearch = item.name.toLowerCase().includes(search.toLowerCase()) || item.category.toLowerCase().includes(search.toLowerCase())
    const matchesStatus = statusFilter === '' || item.status === statusFilter
    return matchesSearch && matchesStatus
  })
  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-bold text-gray-800">Inventory Management</h3>
          <button className="bg-blue-700 text-white flex items-center gap-2 px-4 py-2 rounded-lg text-sm hover:bg-blue-800"><Plus size={16} /> Add Item</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search inventory..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
              <option value="">All Status</option>
              <option value="in_stock">In Stock</option>
              <option value="low_stock">Low Stock</option>
              <option value="out_of_stock">Out of Stock</option>
            </select>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead><tr className="border-b border-gray-100">
              {['Item ID', 'Name', 'Category', 'Quantity', 'Min Stock', 'Unit', 'Department', 'Last Restocked', 'Status'].map(h => (
                <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
              ))}
            </tr></thead>
            <tbody>
              {filtered.map(item => (
                <tr key={item.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-3 px-4 text-xs font-mono text-gray-500">{item.id}</td>
                  <td className="py-3 px-4 text-xs font-semibold text-gray-800">{item.name}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{item.category}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{item.quantity}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{item.minStock}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{item.unit}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{item.department}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{item.lastRestocked}</td>
                  <td className="py-3 px-4"><span className={`badge badge-${item.status === 'in_stock' ? 'approved' : item.status === 'low_stock' ? 'pending' : 'rejected'}`}>{item.status.replace('_', ' ')}</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}
