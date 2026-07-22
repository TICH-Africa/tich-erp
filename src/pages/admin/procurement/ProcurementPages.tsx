import { useState } from 'react'
import {
  REQUISITIONS, SUPPLIERS, PURCHASE_ORDERS, PROCUREMENT_INVOICES, ASSETS, INVENTORY,
  type Requisition, type Supplier, type PurchaseOrder, type ProcurementInvoice, type Asset, type InventoryItem
} from '@/data/mock'
import {
  Search, Plus, ChevronRight,
  BarChart3, DollarSign,
  TrendingUp, TrendingDown, ClipboardList, Building2, Package, ShoppingCart, FileText, Warehouse
} from 'lucide-react'

type ProcurementSubPage = 'overview' | 'requisitions' | 'suppliers' | 'orders' | 'invoices' | 'assets' | 'inventory'

interface Props {
  initialSubPage?: ProcurementSubPage
}

export default function ProcurementPages({ initialSubPage = 'overview' }: Props) {
  const [activeSubPage, setActiveSubPage] = useState<ProcurementSubPage>(initialSubPage)

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

      {activeSubPage === 'overview' && <ProcurementOverview onNavigate={setActiveSubPage} />}
      {activeSubPage === 'requisitions' && <RequisitionsPage requisitions={REQUISITIONS} />}
      {activeSubPage === 'suppliers' && <SuppliersPage suppliers={SUPPLIERS} />}
      {activeSubPage === 'orders' && <OrdersPage orders={PURCHASE_ORDERS} />}
      {activeSubPage === 'invoices' && <ProcurementInvoicesPage invoices={PROCUREMENT_INVOICES} />}
      {activeSubPage === 'assets' && <AssetsPage assets={ASSETS} />}
      {activeSubPage === 'inventory' && <InventoryPage items={INVENTORY} />}
    </div>
  )
}

export function renderProcurementPages(subPage: ProcurementSubPage) {
  return <ProcurementPages key={subPage} initialSubPage={subPage} />
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
          <h3 className="font-700 text-gray-800 mb-4" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Recent Requisitions</h3>
          <div className="space-y-3">
            {REQUISITIONS.slice(0, 5).map(r => (
              <div key={r.id} className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                  <p className="text-xs font-600 text-gray-800">{r.title}</p>
                  <p className="text-[11px] text-gray-400">{r.department} · {r.createdAt}</p>
                </div>
                <div className="text-right">
                  <p className="text-xs font-600 text-gray-700">KES {r.budget.toLocaleString()}</p>
                  <span className={`badge badge-${r.status === 'approved' ? 'approved' : r.status === 'pending' ? 'pending' : 'review'}`}>{r.status}</span>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <h3 className="font-700 text-gray-800 mb-4" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Recent Purchase Orders</h3>
          <div className="space-y-3">
            {PURCHASE_ORDERS.map(po => (
              <div key={po.id} className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                  <p className="text-xs font-600 text-gray-800">{po.id}</p>
                  <p className="text-[11px] text-gray-400">{po.supplierId} · {po.orderDate}</p>
                </div>
                <div className="text-right">
                  <p className="text-xs font-600 text-gray-700">KES {po.amount.toLocaleString()}</p>
                  <span className={`badge badge-${po.status === 'delivered' ? 'approved' : po.status === 'sent' ? 'pending' : 'review'}`}>{po.status}</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Assets Registry</h3>
           <button onClick={() => onNavigate('assets')} className="text-xs text-green-700 font-semibold flex items-center gap-1 hover:gap-2 transition-all">View All <ChevronRight size={14} /></button>
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
                  <td className="py-3 px-4 text-xs font-600 text-gray-800">{asset.name}</td>
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
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Requisition Requests</h3>
          <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> New Requisition</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search requisitions..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
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
                  <td className="py-3 px-4 text-xs font-600 text-gray-800">{r.title}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{r.department}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{r.requestedBy}</td>
                  <td className="py-3 px-4 text-xs font-600 text-gray-700">KES {r.budget.toLocaleString()}</td>
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
    const matchesSearch = s.name.toLowerCase().includes(search.toLowerCase()) || s.category.toLowerCase().includes(search.toLowerCase()) || s.email.toLowerCase().includes(search.toLowerCase())
    const matchesStatus = statusFilter === '' || s.status === statusFilter
    return matchesSearch && matchesStatus
  })

  return (
    <div className="space-y-4">
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Supplier Directory</h3>
          <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> Add Supplier</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search suppliers..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
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
                  <td className="py-3 px-4 text-xs font-600 text-gray-800">{s.name}</td>
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
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Purchase Orders</h3>
          <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> New Order</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search orders..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
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
                  <td className="py-3 px-4 text-xs font-600 text-gray-700">KES {o.amount.toLocaleString()}</td>
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

function ProcurementInvoicesPage({ invoices }: { invoices: ProcurementInvoice[] }) {
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
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Procurement Invoices</h3>
          <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> New Invoice</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search invoices..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
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
                  <td className="py-3 px-4 text-xs font-600 text-gray-700">KES {inv.amount.toLocaleString()}</td>
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
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Asset Registry</h3>
          <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> Add Asset</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search assets..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
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
                  <td className="py-3 px-4 text-xs font-600 text-gray-800">{a.name}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{a.category}</td>
                  <td className="py-3 px-4 text-xs text-gray-600">{a.serialNumber}</td>
                  <td className="py-3 px-4 text-xs font-600 text-gray-700">KES {a.cost.toLocaleString()}</td>
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
          <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Inventory Management</h3>
          <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> Add Item</button>
        </div>
        <div className="p-4 bg-gray-50 border-b border-gray-100">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search inventory..." value={search} onChange={e => setSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
            </div>
            <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white">
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
                  <td className="py-3 px-4 text-xs font-600 text-gray-800">{item.name}</td>
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
