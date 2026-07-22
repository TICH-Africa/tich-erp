import { useState } from 'react'
import {
  INVOICES, BILLS, BANK_TRANSACTIONS, CHART_OF_ACCOUNTS,
  VENDORS, CUSTOMERS, JOURNAL_ENTRIES, REVENUE_DATA, type Customer
} from '@/data/mock'
import {
  BarChart3, TrendingUp, DollarSign, Receipt, FileText, Download,
  Search, ChevronRight, Eye, Plus, Wallet,
  Users, Building2, BookOpen, CheckCircle,
  ClockIcon, CreditCard, Landmark, Calculator, X, Printer
} from 'lucide-react'

type FinanceSubPage = 'overview' | 'invoices' | 'bills' | 'banking' | 'accounts' | 'reports' | 'customers' | 'vendors' | 'journal'

interface Props {
  initialSubPage?: FinanceSubPage
}

export default function FinancePages({ initialSubPage = 'overview' }: Props) {
  const [activeSubPage, setActiveSubPage] = useState<FinanceSubPage>(initialSubPage)
  const [selectedStudent, setSelectedStudent] = useState<Customer | null>(null)
  const [studentSearch, setStudentSearch] = useState('')
  const [studentStatusFilter, setStudentStatusFilter] = useState('')
  const [vendorPaymentStatuses, setVendorPaymentStatuses] = useState<Record<string, 'paid' | 'pending' | 'in_review'>>(
    Object.fromEntries(VENDORS.map(v => [v.id, v.paymentStatus]))
  )
  const [invoiceSearch, setInvoiceSearch] = useState('')
  const [invoiceStatusFilter, setInvoiceStatusFilter] = useState('')
  const [billSearch, setBillSearch] = useState('')
  const [billStatusFilter, setBillStatusFilter] = useState('')
  const [vendorSearch, setVendorSearch] = useState('')
  const [vendorCategoryFilter, setVendorCategoryFilter] = useState('')
  const [journalSearch, setJournalSearch] = useState('')
  const [journalTypeFilter, setJournalTypeFilter] = useState('')

  const subNavItems: { label: string; page: FinanceSubPage; icon: React.ReactNode }[] = [
    { label: 'Overview', page: 'overview', icon: <BarChart3 size={15} /> },
    { label: 'Invoices', page: 'invoices', icon: <Receipt size={15} /> },
    { label: 'Bills', page: 'bills', icon: <FileText size={15} /> },
    { label: 'Banking', page: 'banking', icon: <Landmark size={15} /> },
    { label: 'Accounts', page: 'accounts', icon: <BookOpen size={15} /> },
    { label: 'Reports', page: 'reports', icon: <Download size={15} /> },
    { label: 'Students', page: 'customers', icon: <Users size={15} /> },
    { label: 'Vendors', page: 'vendors', icon: <Building2 size={15} /> },
    { label: 'Journal', page: 'journal', icon: <Calculator size={15} /> },
  ]

  const totalRevenue = INVOICES.filter(i => i.status === 'paid').reduce((s, i) => s + i.total, 0)
  const totalOutstanding = INVOICES.filter(i => ['sent', 'overdue'].includes(i.status)).reduce((s, i) => s + i.total, 0)
  const totalBills = BILLS.reduce((s, b) => s + b.amount, 0)
  const bankBalance = BANK_TRANSACTIONS[0]?.balance || 0

  const filteredStudents = CUSTOMERS.filter(c => {
    const matchesSearch = c.name.toLowerCase().includes(studentSearch.toLowerCase()) ||
      c.studentId.toLowerCase().includes(studentSearch.toLowerCase()) ||
      c.program.toLowerCase().includes(studentSearch.toLowerCase()) ||
      c.email.toLowerCase().includes(studentSearch.toLowerCase())
    const matchesStatus = studentStatusFilter === '' || c.status === studentStatusFilter
    return matchesSearch && matchesStatus
  })

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
            <StatCard label="Total Revenue" value={`KES ${(totalRevenue / 1000000).toFixed(1)}M`} sub="FY 2025" icon={<TrendingUp size={22} />} />
            <StatCard label="Outstanding AR" value={`KES ${(totalOutstanding / 1000000).toFixed(1)}M`} sub="Pending collection" icon={<Receipt size={22} />} color="#d97706" />
            <StatCard label="Total Bills" value={`KES ${(totalBills / 1000000).toFixed(1)}M`} sub="Accounts payable" icon={<FileText size={22} />} color="#dc2626" />
            <StatCard label="Bank Balance" value={`KES ${(bankBalance / 1000000).toFixed(1)}M`} sub="Main account" icon={<Wallet size={22} />} color="#1d4ed8" />
          </div>
          <div className="grid md:grid-cols-2 gap-5">
            <div className="bg-white border border-gray-100 rounded-xl p-5">
              <h3 className="font-700 text-gray-800 mb-4" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Monthly Revenue</h3>
              <div className="space-y-3">
                {REVENUE_DATA.map(r => (
                  <div key={r.month}>
                    <div className="flex items-center justify-between mb-1">
                      <span className="text-xs font-500 text-gray-700">{r.month}</span>
                      <span className="text-xs font-600 text-green-700">KES {((r.tuition + r.accommodation + r.other) / 1000000).toFixed(1)}M</span>
                    </div>
                    <div className="relative bg-gray-100 rounded-full h-2">
                      <div className="h-full bg-green-500 rounded-full" style={{ width: `${((r.tuition + r.accommodation + r.other) / 25000000) * 100}%` }} />
                    </div>
                  </div>
                ))}
              </div>
            </div>
            <div className="bg-white border border-gray-100 rounded-xl p-5">
              <h3 className="font-700 text-gray-800 mb-4" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Quick Actions</h3>
              <div className="space-y-2.5">
                <button className="w-full flex items-center gap-3 p-3 bg-gray-50 border border-gray-100 rounded-lg hover:bg-green-50 hover:border-green-200 transition-colors">
                  <Plus size={18} className="text-green-700" />
                  <div className="text-left">
                    <p className="text-xs font-600 text-gray-800">Create Invoice</p>
                    <p className="text-[10px] text-gray-400">New student fee invoice</p>
                  </div>
                </button>
                <button className="w-full flex items-center gap-3 p-3 bg-gray-50 border border-gray-100 rounded-lg hover:bg-green-50 hover:border-green-200 transition-colors">
                  <Receipt size={18} className="text-blue-700" />
                  <div className="text-left">
                    <p className="text-xs font-600 text-gray-800">Record Expense</p>
                    <p className="text-[10px] text-gray-400">Add new expense or bill</p>
                  </div>
                </button>
                <button className="w-full flex items-center gap-3 p-3 bg-gray-50 border border-gray-100 rounded-lg hover:bg-green-50 hover:border-green-200 transition-colors">
                  <Download size={18} className="text-purple-700" />
                  <div className="text-left">
                    <p className="text-xs font-600 text-gray-800">Run Payroll</p>
                    <p className="text-[10px] text-gray-400">Process staff salaries</p>
                  </div>
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {activeSubPage === 'invoices' && (
        <div className="space-y-4">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search invoices..." value={invoiceSearch} onChange={e => setInvoiceSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
            </div>
            <select value={invoiceStatusFilter} onChange={e => setInvoiceStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 bg-white">
              <option value="">All Status</option>
              <option value="paid">Paid</option>
              <option value="sent">Sent</option>
              <option value="overdue">Overdue</option>
              <option value="draft">Draft</option>
            </select>
            <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> New Invoice</button>
          </div>
          <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead><tr className="border-b border-gray-100">
                  {['Invoice', 'Customer', 'Program', 'Amount', 'Issue Date', 'Due Date', 'Status'].map(h => (
                    <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
                  ))}
                </tr></thead>
                <tbody>
                  {INVOICES.filter(inv => {
                    const matchesSearch = inv.customerName.toLowerCase().includes(invoiceSearch.toLowerCase()) || inv.invoiceNo.toLowerCase().includes(invoiceSearch.toLowerCase()) || inv.program?.toLowerCase().includes(invoiceSearch.toLowerCase())
                    const matchesStatus = invoiceStatusFilter === '' || inv.status === invoiceStatusFilter
                    return matchesSearch && matchesStatus
                  }).map(inv => (
                    <tr key={inv.id} className="border-b border-gray-50 hover:bg-gray-50">
                      <td className="py-3 px-4 text-xs font-600 text-gray-800">{inv.invoiceNo}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{inv.customerName}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{inv.program}</td>
                      <td className="py-3 px-4 text-xs font-600 text-gray-700">KES {inv.total.toLocaleString()}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{inv.issueDate}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{inv.dueDate}</td>
                      <td className="py-3 px-4"><span className={`badge ${inv.status === 'paid' ? 'badge-approved' : inv.status === 'overdue' ? 'badge-rejected' : inv.status === 'sent' ? 'badge-pending' : 'bg-gray-100 text-gray-600'}`}>{inv.status}</span></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {activeSubPage === 'bills' && (
        <div className="space-y-4">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input type="text" placeholder="Search bills..." value={billSearch} onChange={e => setBillSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
            </div>
            <select value={billStatusFilter} onChange={e => setBillStatusFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 bg-white">
              <option value="">All Status</option>
              <option value="paid">Paid</option>
              <option value="pending">Pending</option>
              <option value="overdue">Overdue</option>
              <option value="draft">Draft</option>
            </select>
            <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> New Bill</button>
          </div>
          <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead><tr className="border-b border-gray-100">
                  {['Bill', 'Vendor', 'Category', 'Amount', 'Due Date', 'Status', 'Approved By'].map(h => (
                    <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
                  ))}
                </tr></thead>
                <tbody>
                  {BILLS.filter(bill => {
                    const matchesSearch = bill.vendorName.toLowerCase().includes(billSearch.toLowerCase()) || bill.billNo.toLowerCase().includes(billSearch.toLowerCase())
                    const matchesStatus = billStatusFilter === '' || bill.status === billStatusFilter
                    return matchesSearch && matchesStatus
                  }).map(bill => (
                    <tr key={bill.id} className="border-b border-gray-50 hover:bg-gray-50">
                      <td className="py-3 px-4 text-xs font-600 text-gray-800">{bill.billNo}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{bill.vendorName}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{bill.category}</td>
                      <td className="py-3 px-4 text-xs font-600 text-gray-700">KES {bill.amount.toLocaleString()}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{bill.dueDate}</td>
                      <td className="py-3 px-4"><span className={`badge ${bill.status === 'paid' ? 'badge-approved' : bill.status === 'overdue' ? 'badge-rejected' : bill.status === 'pending' ? 'badge-pending' : 'bg-gray-100 text-gray-600'}`}>{bill.status}</span></td>
                      <td className="py-3 px-4 text-xs text-gray-600">{bill.approvedBy || '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {activeSubPage === 'banking' && (
        <div className="space-y-4">
          <div className="bg-white border border-gray-100 rounded-xl p-5">
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Bank Accounts</h3>
              <button className="btn-outline text-xs flex items-center gap-1"><Download size={12} /> Export Statement</button>
            </div>
            <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
              <div className="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-4">
                <p className="text-xs text-green-600 font-medium">Main Account</p>
                <p className="text-lg font-800 text-green-800 mt-1" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>KES {(bankBalance / 1000000).toFixed(1)}M</p>
                <p className="text-[10px] text-green-600 mt-0.5">Equity Bank · 1234567890</p>
              </div>
              <div className="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-4">
                <p className="text-xs text-blue-600 font-medium">M-PESA</p>
                <p className="text-lg font-800 text-blue-800 mt-1" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>KES 2.4M</p>
                <p className="text-[10px] text-blue-600 mt-0.5">Till · 2345678</p>
              </div>
              <div className="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-xl p-4">
                <p className="text-xs text-purple-600 font-medium">Payroll Account</p>
                <p className="text-lg font-800 text-purple-800 mt-1" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>KES 1.8M</p>
                <p className="text-[10px] text-purple-600 mt-0.5">KCB Bank · 9876543210</p>
              </div>
            </div>
          </div>
          <div className="bg-white border border-gray-100 rounded-xl p-5">
            <h3 className="font-700 text-gray-800 mb-4" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Recent Transactions</h3>
            <div className="space-y-3">
              {BANK_TRANSACTIONS.map(txn => (
                <div key={txn.id} className="flex items-center justify-between p-3 bg-gray-50 border border-gray-100 rounded-lg">
                  <div className="flex items-center gap-3">
                    <div className={`w-8 h-8 rounded-full flex items-center justify-center ${txn.type === 'credit' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                      <CreditCard size={14} />
                    </div>
                    <div>
                      <p className="text-xs font-600 text-gray-800">{txn.description}</p>
                      <p className="text-[10px] text-gray-400">{txn.date} · {txn.reference}</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className={`text-xs font-600 ${txn.type === 'credit' ? 'text-green-700' : 'text-red-700'}`}>{txn.type === 'credit' ? '+' : '-'}KES {txn.amount.toLocaleString()}</p>
                    <div className="flex items-center gap-1 justify-end">
                      <span className="text-[10px] text-gray-400">Bal: KES {txn.balance.toLocaleString()}</span>
                      {txn.reconciled ? <CheckCircle size={10} className="text-green-500" /> : <ClockIcon size={10} className="text-yellow-500" />}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {activeSubPage === 'accounts' && (
        <div className="space-y-4">
          <div className="bg-white border border-gray-100 rounded-xl p-5">
            <div className="flex items-center justify-between mb-4">
              <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Chart of Accounts</h3>
              <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> Add Account</button>
            </div>
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
              {CHART_OF_ACCOUNTS.map(acc => (
                <div key={acc.id} className="p-4 bg-gray-50 border border-gray-100 rounded-lg hover:shadow-sm transition-shadow">
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-[10px] font-mono text-gray-400">{acc.code}</span>
                    <span className={`text-[10px] px-2 py-0.5 rounded ${acc.type === 'asset' ? 'bg-green-100 text-green-700' : acc.type === 'liability' ? 'bg-red-100 text-red-700' : acc.type === 'equity' ? 'bg-purple-100 text-purple-700' : acc.type === 'revenue' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700'}`}>{acc.type}</span>
                  </div>
                  <p className="text-sm font-600 text-gray-800 mb-1">{acc.name}</p>
                  <p className="text-xs font-600 text-gray-600">KES {acc.balance.toLocaleString()}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {activeSubPage === 'reports' && (
        <div className="space-y-5">
          <div className="bg-white border border-gray-100 rounded-xl p-5">
            <div className="flex items-center justify-between mb-4">
              <div>
                <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Financial Reports</h3>
                <p className="text-xs text-gray-500 mt-0.5">Generate and export standard financial statements</p>
              </div>
              <div className="flex gap-2">
                <select className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 bg-white">
                  <option>July 2025</option>
                  <option>June 2025</option>
                  <option>May 2025</option>
                </select>
                <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Download size={16} /> Export All</button>
              </div>
            </div>
          </div>
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {[
              { name: 'Profit & Loss', desc: 'Revenue, expenses, and net income', icon: <BarChart3 size={20} /> },
              { name: 'Balance Sheet', desc: 'Assets, liabilities, and equity', icon: <BookOpen size={20} /> },
              { name: 'Cash Flow Statement', desc: 'Operating, investing, financing', icon: <TrendingUp size={20} /> },
              { name: 'General Ledger', desc: 'All journal entries by account', icon: <Calculator size={20} /> },
              { name: 'Trial Balance', desc: 'Debit/credit balance check', icon: <FileText size={20} /> },
              { name: 'AR Aging', desc: 'Outstanding receivables by age', icon: <Receipt size={20} /> },
              { name: 'AP Aging', desc: 'Outstanding payables by age', icon: <FileText size={20} /> },
              { name: 'Payroll Summary', desc: 'Gross, deductions, net pay', icon: <DollarSign size={20} /> },
              { name: 'Audit Log', desc: 'Financial transaction history', icon: <Eye size={20} /> },
            ].map(report => (
              <div key={report.name} className="bg-white border border-gray-100 rounded-xl p-5 hover:shadow-md hover:border-green-200 transition-all group cursor-pointer">
                <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-700 mb-3 group-hover:bg-green-200 transition-colors">
                  {report.icon}
                </div>
                <h4 className="font-700 text-sm text-gray-900 mb-1" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>{report.name}</h4>
                <p className="text-xs text-gray-500 mb-3">{report.desc}</p>
                <button className="text-xs text-green-700 font-semibold flex items-center gap-1 hover:gap-2 transition-all">Generate <ChevronRight size={14} /></button>
              </div>
            ))}
          </div>
        </div>
      )}

      {activeSubPage === 'customers' && (
        <div className="space-y-4">
          <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Students</h3>
              <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> Add Student</button>
            </div>
            <div className="p-4 bg-gray-50 border-b border-gray-100">
              <div className="flex flex-col sm:flex-row gap-3">
                <div className="relative flex-1">
                  <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                  <input
                    type="text"
                    placeholder="Search by name, ID, program or email..."
                    value={studentSearch}
                    onChange={e => setStudentSearch(e.target.value)}
                    className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100"
                  />
                </div>
                <select
                  value={studentStatusFilter}
                  onChange={e => setStudentStatusFilter(e.target.value)}
                  className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 bg-white"
                >
                  <option value="">All Status</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead><tr className="border-b border-gray-100">
                  {['Student ID', 'Student', 'Program', 'Period', 'Total Fee', 'Paid', 'Balance', 'Status'].map(h => (
                    <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
                  ))}
                </tr></thead>
                <tbody>
                  {filteredStudents.map(c => (
                    <tr key={c.id} className="border-b border-gray-50 hover:bg-gray-50 cursor-pointer" onDoubleClick={() => setSelectedStudent(c)}>
                      <td className="py-3 px-4 text-xs font-mono text-gray-500">{c.studentId}</td>
                      <td className="py-3 px-4">
                        <div>
                          <p className="text-xs font-600 text-gray-800">{c.name}</p>
                          <p className="text-[10px] text-gray-400">{c.email}</p>
                        </div>
                      </td>
                      <td className="py-3 px-4 text-xs text-gray-600">{c.program}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{c.period}</td>
                      <td className="py-3 px-4 text-xs font-600 text-gray-700">KES {c.totalFee.toLocaleString()}</td>
                      <td className="py-3 px-4 text-xs text-green-600">KES {c.totalPaid.toLocaleString()}</td>
                      <td className="py-3 px-4 text-xs font-600 text-orange-600">KES {c.outstanding.toLocaleString()}</td>
                      <td className="py-3 px-4"><span className={`badge ${c.status === 'active' ? 'badge-approved' : 'bg-gray-100 text-gray-600'}`}>{c.status}</span></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            {filteredStudents.length === 0 && (
              <p className="text-center text-gray-400 text-sm py-8">No students match your search criteria.</p>
            )}
          </div>
        </div>
      )}

      {activeSubPage === 'vendors' && (
        <div className="space-y-4">
          <div className="p-4 bg-gray-50 border-b border-gray-100">
            <div className="flex flex-col sm:flex-row gap-3">
              <div className="relative flex-1">
                <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                <input type="text" placeholder="Search vendors..." value={vendorSearch} onChange={e => setVendorSearch(e.target.value)} className="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100" />
              </div>
              <select value={vendorCategoryFilter} onChange={e => setVendorCategoryFilter(e.target.value)} className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 bg-white">
                <option value="">All Categories</option>
                {[...new Set(VENDORS.map(v => v.category))].map(c => <option key={c} value={c}>{c}</option>)}
              </select>
            </div>
          </div>
          <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Vendors</h3>
              <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> Add Vendor</button>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead><tr className="border-b border-gray-100">
                  {['Vendor', 'Category', 'Email', 'Phone', 'Total Paid', 'Outstanding', 'Payment Status'].map(h => (
                    <th key={h} className="text-left py-3 px-4 text-gray-500 font-600 text-xs">{h}</th>
                  ))}
                </tr></thead>
                <tbody>
                  {VENDORS.filter(v => {
                    const matchesSearch = v.name.toLowerCase().includes(vendorSearch.toLowerCase()) || v.email.toLowerCase().includes(vendorSearch.toLowerCase())
                    const matchesCategory = vendorCategoryFilter === '' || v.category === vendorCategoryFilter
                    return matchesSearch && matchesCategory
                  }).map(v => (
                    <tr key={v.id} className="border-b border-gray-50 hover:bg-gray-50">
                      <td className="py-3 px-4 text-xs font-600 text-gray-800">{v.name}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{v.category}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{v.email}</td>
                      <td className="py-3 px-4 text-xs text-gray-600">{v.phone}</td>
                      <td className="py-3 px-4 text-xs text-green-600">KES {v.totalPaid.toLocaleString()}</td>
                      <td className="py-3 px-4 text-xs font-600 text-orange-600">KES {v.outstanding.toLocaleString()}</td>
                      <td className="py-3 px-4">
                        <div className="flex items-center gap-1">
                          {(['paid', 'pending', 'in_review'] as const).map(status => {
                            const currentStatus = vendorPaymentStatuses[v.id] || v.paymentStatus
                            const isActive = currentStatus === status
                            const colors = {
                              paid: isActive ? 'bg-green-100 text-green-700 ring-1 ring-green-300' : 'bg-gray-50 text-gray-400 hover:bg-green-50',
                              pending: isActive ? 'bg-yellow-100 text-yellow-700 ring-1 ring-yellow-300' : 'bg-gray-50 text-gray-400 hover:bg-yellow-50',
                              in_review: isActive ? 'bg-blue-100 text-blue-700 ring-1 ring-blue-300' : 'bg-gray-50 text-gray-400 hover:bg-blue-50',
                            }
                            const labels = { paid: 'Paid', pending: 'Pending', in_review: 'In Review' }
                            return (
                              <button
                                key={status}
                                onClick={() => setVendorPaymentStatuses(prev => ({ ...prev, [v.id]: status }))}
                                className={`px-2.5 py-1 rounded text-[10px] font-medium transition-colors ${colors[status]}`}>
                                {labels[status]}
                              </button>
                            )
                          })}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {activeSubPage === 'journal' && (
        <div className="space-y-4">
          <div className="bg-white border border-gray-100 rounded-xl p-5">
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 gap-3">
              <h3 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Journal Entries</h3>
              <div className="flex gap-2">
                <div className="relative">
                  <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                  <input type="text" placeholder="Search entries..." value={journalSearch} onChange={e => setJournalSearch(e.target.value)}
                    className="border border-gray-200 rounded-lg pl-9 pr-3 py-1.5 text-xs focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 w-48" />
                </div>
                <select value={journalTypeFilter} onChange={e => setJournalTypeFilter(e.target.value)}
                  className="border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:border-green-500 bg-white">
                  <option value="">All Types</option>
                  <option value="debit">Debit</option>
                  <option value="credit">Credit</option>
                </select>
                <button className="btn-primary flex items-center gap-2 px-4 py-2 text-sm"><Plus size={16} /> New Entry</button>
              </div>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead><tr className="border-b border-gray-100">
                  {['Date', 'Account', 'Description', 'Debit', 'Credit', 'Reference'].map(h => (
                    <th key={h} className="text-left py-2.5 px-3 text-gray-500 font-600 text-xs">{h}</th>
                  ))}
                </tr></thead>
                <tbody>
                  {JOURNAL_ENTRIES.filter(j => {
                    const matchesSearch = j.account.toLowerCase().includes(journalSearch.toLowerCase()) || j.description.toLowerCase().includes(journalSearch.toLowerCase()) || j.reference.toLowerCase().includes(journalSearch.toLowerCase())
                    const matchesType = journalTypeFilter === '' || (journalTypeFilter === 'debit' && j.debit > 0) || (journalTypeFilter === 'credit' && j.credit > 0)
                    return matchesSearch && matchesType
                  }).map(j => (
                    <tr key={j.id} className="border-b border-gray-50 hover:bg-gray-50">
                      <td className="py-2.5 px-3 text-xs text-gray-600">{j.date}</td>
                      <td className="py-2.5 px-3 text-xs font-600 text-gray-800">{j.account}</td>
                      <td className="py-2.5 px-3 text-xs text-gray-600">{j.description}</td>
                      <td className="py-2.5 px-3 text-xs font-600 text-green-700">{j.debit > 0 ? `KES ${j.debit.toLocaleString()}` : '—'}</td>
                      <td className="py-2.5 px-3 text-xs font-600 text-red-700">{j.credit > 0 ? `KES ${j.credit.toLocaleString()}` : '—'}</td>
                      <td className="py-2.5 px-3 text-xs text-gray-500">{j.reference}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {selectedStudent && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" onClick={() => setSelectedStudent(null)}>
          <div className="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto" onClick={e => e.stopPropagation()}>
            <div className="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between z-10">
              <div>
                <h3 className="font-700 text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Student Account Statement</h3>
                <p className="text-xs text-gray-500">{selectedStudent.studentId} · {selectedStudent.program} · {selectedStudent.period}</p>
              </div>
              <div className="flex items-center gap-2">
                <button onClick={() => window.print()} className="btn-outline flex items-center gap-2 px-3 py-2 text-xs"><Printer size={14} /> Print</button>
                <button className="btn-primary flex items-center gap-2 px-3 py-2 text-xs"><Download size={14} /> Download Statement</button>
                <button onClick={() => setSelectedStudent(null)} className="p-2 hover:bg-gray-100 rounded-lg"><X size={18} /></button>
              </div>
            </div>

            <div className="p-6 space-y-6">
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="bg-gray-50 rounded-xl p-4">
                  <p className="text-xs text-gray-500 mb-1">Student Name</p>
                  <p className="text-sm font-600 text-gray-800">{selectedStudent.name}</p>
                </div>
                <div className="bg-gray-50 rounded-xl p-4">
                  <p className="text-xs text-gray-500 mb-1">Student ID</p>
                  <p className="text-sm font-mono text-gray-800">{selectedStudent.studentId}</p>
                </div>
                <div className="bg-gray-50 rounded-xl p-4">
                  <p className="text-xs text-gray-500 mb-1">Program</p>
                  <p className="text-sm text-gray-800">{selectedStudent.program}</p>
                </div>
                <div className="bg-gray-50 rounded-xl p-4">
                  <p className="text-xs text-gray-500 mb-1">Period / Year</p>
                  <p className="text-sm text-gray-800">{selectedStudent.period}</p>
                </div>
                <div className="bg-gray-50 rounded-xl p-4">
                  <p className="text-xs text-gray-500 mb-1">Email</p>
                  <p className="text-sm text-gray-800">{selectedStudent.email}</p>
                </div>
                <div className="bg-gray-50 rounded-xl p-4">
                  <p className="text-xs text-gray-500 mb-1">Phone</p>
                  <p className="text-sm text-gray-800">{selectedStudent.phone}</p>
                </div>
                <div className="bg-gray-50 rounded-xl p-4">
                  <p className="text-xs text-gray-500 mb-1">Admission Date</p>
                  <p className="text-sm text-gray-800">{selectedStudent.admissionDate}</p>
                </div>
                <div className="bg-gray-50 rounded-xl p-4">
                  <p className="text-xs text-gray-500 mb-1">Year of Study</p>
                  <p className="text-sm text-gray-800">Year {selectedStudent.yearOfStudy} · {selectedStudent.semester}</p>
                </div>
              </div>

              <div className="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-5">
                <h4 className="font-700 text-gray-800 mb-4" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Financial Summary</h4>
                <div className="grid grid-cols-3 gap-4">
                  <div>
                    <p className="text-xs text-gray-600 mb-1">Total Fee</p>
                    <p className="text-lg font-800 text-gray-900" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>KES {selectedStudent.totalFee.toLocaleString()}</p>
                  </div>
                  <div>
                    <p className="text-xs text-gray-600 mb-1">Total Paid</p>
                    <p className="text-lg font-800 text-green-700" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>KES {selectedStudent.totalPaid.toLocaleString()}</p>
                  </div>
                  <div>
                    <p className="text-xs text-gray-600 mb-1">Balance</p>
                    <p className="text-lg font-800 text-orange-600" style={{ fontFamily: 'var(--font-sans)', fontWeight: 800 }}>KES {selectedStudent.outstanding.toLocaleString()}</p>
                  </div>
                </div>
                <div className="mt-4 bg-white rounded-lg p-3">
                  <div className="flex items-center justify-between mb-1">
                    <span className="text-xs text-gray-600">Payment Progress</span>
                    <span className="text-xs font-600 text-gray-800">{Math.round((selectedStudent.totalPaid / selectedStudent.totalFee) * 100)}%</span>
                  </div>
                  <div className="w-full bg-gray-200 rounded-full h-2.5">
                    <div className="h-full bg-green-500 rounded-full transition-all" style={{ width: `${(selectedStudent.totalPaid / selectedStudent.totalFee) * 100}%` }} />
                  </div>
                </div>
              </div>

              <div>
                <div className="flex items-center justify-between mb-4">
                  <h4 className="font-700 text-gray-800" style={{ fontFamily: 'var(--font-sans)', fontWeight: 700 }}>Payment History</h4>
                  <span className="text-xs text-gray-500">{selectedStudent.payments.length} transaction{selectedStudent.payments.length !== 1 ? 's' : ''}</span>
                </div>
                {selectedStudent.payments.length === 0 ? (
                  <div className="text-center py-8 bg-gray-50 rounded-xl">
                    <p className="text-sm text-gray-400">No payments recorded yet.</p>
                  </div>
                ) : (
                  <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
                    <div className="overflow-x-auto">
                      <table className="w-full">
                        <thead><tr className="border-b border-gray-100 bg-gray-50">
                          {['Date', 'Reference', 'Category', 'Method', 'Amount', 'Status', 'Received By', 'Notes'].map(h => (
                            <th key={h} className="text-left py-2.5 px-3 text-gray-500 font-600 text-xs">{h}</th>
                          ))}
                        </tr></thead>
                        <tbody>
                          {selectedStudent.payments.map(payment => (
                            <tr key={payment.id} className="border-b border-gray-50 hover:bg-gray-50">
                              <td className="py-2.5 px-3 text-xs text-gray-600">{payment.date}</td>
                              <td className="py-2.5 px-3 text-xs font-mono text-gray-500">{payment.reference}</td>
                              <td className="py-2.5 px-3">
                                <span className="text-xs bg-green-50 text-green-700 px-2 py-0.5 rounded">{payment.category}</span>
                              </td>
                              <td className="py-2.5 px-3 text-xs text-gray-600">{payment.method}</td>
                              <td className="py-2.5 px-3 text-xs font-600 text-gray-800">KES {payment.amount.toLocaleString()}</td>
                              <td className="py-2.5 px-3">
                                <span className={`badge ${payment.status === 'completed' ? 'badge-approved' : payment.status === 'pending' ? 'badge-pending' : 'badge-rejected'}`}>
                                  {payment.status}
                                </span>
                              </td>
                              <td className="py-2.5 px-3 text-xs text-gray-600">{payment.receivedBy}</td>
                              <td className="py-2.5 px-3 text-xs text-gray-400">{payment.notes || '—'}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
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

export function renderFinancePages(subPage: 'overview' | 'invoices' | 'bills' | 'banking' | 'accounts' | 'reports' | 'customers' | 'vendors' | 'journal') {
  return <FinancePages key={subPage} initialSubPage={subPage} />
}
