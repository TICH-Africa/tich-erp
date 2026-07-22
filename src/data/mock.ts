export type Role =
  | 'super_admin'
  | 'ceo'
  | 'academic_registrar'
  | 'hod'
  | 'admissions_officer'
  | 'finance_manager'
  | 'hr_manager'
  | 'qa_officer';

export interface User {
  id: string;
  name: string;
  role: Role;
  department?: string;
  email: string;
  avatar: string;
}

export interface Application {
  id: string;
  studentName: string;
  studentId: string;
  program: string;
  faculty: string;
  appliedDate: string;
  documents: string[];
  gpa?: number;
  status: 'pending' | 'under_review' | 'approved' | 'rejected' | 'offer_sent' | 'registered';
  reviewedBy?: string;
  notes?: string;
  nationality: string;
  email: string;
  paymentStatus: 'unpaid' | 'partial' | 'paid';
  amountPaid: number;
  registrationFee: number;
  offerSent?: boolean;
  offerSentDate?: string;
  registeredDate?: string;
}

export const ROLES: Record<Role, { label: string; color: string; description: string }> = {
  super_admin:        { label: 'Super Admin',         color: '#7c3aed', description: 'Full system access' },
  ceo:                { label: 'CEO',                  color: '#0f172a', description: 'Chief Executive Officer' },
  academic_registrar: { label: 'Academic Registrar',  color: '#15803d', description: 'Student records & approvals' },
  hod:                { label: 'HOD',                  color: '#0d9488', description: 'Department head' },
  admissions_officer: { label: 'Admissions Officer',  color: '#d97706', description: 'Student intake & applications' },
  finance_manager:    { label: 'Finance Manager',      color: '#dc2626', description: 'Financial operations' },
  hr_manager:         { label: 'HR Manager',           color: '#9333ea', description: 'Human resources' },
  qa_officer:         { label: 'QA Officer',           color: '#0891b2', description: 'Quality assurance' },
};

export const DEMO_USERS: User[] = [
  { id: 'u1', name: 'Dr. Amara Osei',    role: 'super_admin',        email: 'a.osei@tich.or.ke',         avatar: 'AO' },
  { id: 'u2', name: 'Prof. James Kariuki', role: 'ceo',              email: 'j.kariuki@tich.or.ke',      avatar: 'JK' },
  { id: 'u3', name: 'Mrs. Grace Mwangi',  role: 'academic_registrar',email: 'g.mwangi@tich.or.ke',      avatar: 'GM' },
  { id: 'u4', name: 'Mr. Patrick Otieno', role: 'hod',               email: 'p.otieno@tich.or.ke',       avatar: 'PO', department: 'Clinical Medicine' },
  { id: 'u5', name: 'Ms. Janet Achieng',  role: 'admissions_officer', email: 'j.achieng@tich.or.ke',     avatar: 'JA' },
  { id: 'u6', name: 'Mr. David Kamau',    role: 'finance_manager',   email: 'd.kamau@tich.or.ke',        avatar: 'DK' },
  { id: 'u7', name: 'Ms. Winnie Adhiambo',role: 'hr_manager',        email: 'w.adhiambo@tich.or.ke',    avatar: 'WA' },
  { id: 'u8', name: 'Dr. Lilian Gitau',   role: 'qa_officer',        email: 'l.gitau@tich.or.ke',        avatar: 'LG' },
];

export const APPLICATIONS: Application[] = [
  { id: 'APP-2401', studentName: 'Brian Otieno',      studentId: 'STU-2401', program: 'Diploma in Community Health and Development', faculty: 'Department of Health and Social Sciences', appliedDate: '2025-06-10', documents: ['National ID', 'KCSE Certificate', 'Passport Photo'], gpa: 3.4, status: 'approved', reviewedBy: 'Mrs. Grace Mwangi', nationality: 'Kenyan', email: 'b.otieno@student.tich.or.ke', paymentStatus: 'paid', amountPaid: 30000, registrationFee: 30000, offerSent: true, offerSentDate: '2025-06-15' },
  { id: 'APP-2402', studentName: 'Amina Hassan',      studentId: 'STU-2402', program: 'Diploma in Clinical Medicine',      faculty: 'Department of Health and Social Sciences', appliedDate: '2025-06-11', documents: ['National ID', 'A-Level Results', 'Recommendation Letter'], gpa: 3.7, status: 'approved', nationality: 'Kenyan', email: 'a.hassan@student.tich.or.ke', paymentStatus: 'partial', amountPaid: 22500, registrationFee: 45000 },
  { id: 'APP-2403', studentName: 'John Mwangi',       studentId: 'STU-2403', program: 'Certificate in Community Health and Development',   faculty: 'Department of Health and Social Sciences', appliedDate: '2025-06-12', documents: ['National ID', 'KCSE Certificate'], gpa: 2.9, status: 'approved', reviewedBy: 'Mrs. Grace Mwangi', nationality: 'Kenyan', email: 'j.mwangi@student.tich.or.ke', paymentStatus: 'paid', amountPaid: 15000, registrationFee: 15000, offerSent: true, offerSentDate: '2025-06-18', registeredDate: '2025-06-20' },
  { id: 'APP-2404', studentName: 'Fatuma Salim',      studentId: 'STU-2404', program: 'Diploma in Food and Beverage (Level 6)',       faculty: 'Department of Catering and Hospitality',     appliedDate: '2025-06-13', documents: ['National ID', 'KCSE Certificate', 'Birth Certificate'], gpa: 3.1, status: 'pending',      nationality: 'Kenyan', email: 'f.salim@student.tich.or.ke', paymentStatus: 'unpaid', amountPaid: 0, registrationFee: 30000 },
  { id: 'APP-2405', studentName: 'Kevin Njoroge',     studentId: 'STU-2405', program: 'Diploma in Agribusiness',   faculty: 'Department of Business and Accounting', appliedDate: '2025-06-14', documents: ['National ID', 'A-Level Results'], gpa: 3.5, status: 'pending',      nationality: 'Kenyan', email: 'k.njoroge@student.tich.or.ke', paymentStatus: 'unpaid', amountPaid: 0, registrationFee: 30000 },
  { id: 'APP-2406', studentName: 'Zuwena Kombo',      studentId: 'STU-2406', program: 'Diploma in Data Science',  faculty: 'Department of Data Science and Analytics', appliedDate: '2025-06-15', documents: ['Degree Certificate', 'Transcripts', 'Research Proposal'], gpa: 3.8, status: 'under_review', nationality: 'Kenyan', email: 'z.kombo@student.tich.or.ke', paymentStatus: 'partial', amountPaid: 10000, registrationFee: 20000 },
  { id: 'APP-2407', studentName: 'Moses Auma',        studentId: 'STU-2407', program: 'Diploma in ICT',          faculty: 'Department of ICT',    appliedDate: '2025-06-16', documents: ['National ID', 'KCSE Certificate'], gpa: 2.8, status: 'rejected', reviewedBy: 'Dr. Samuel Banda', notes: 'Incomplete documentation', nationality: 'Kenyan', email: 'm.auma@student.tich.or.ke', paymentStatus: 'unpaid', amountPaid: 0, registrationFee: 20000 },
  { id: 'APP-2408', studentName: 'Rehema Ally',       studentId: 'STU-2408', program: 'Artisan in Computer Repair and Maintenance',   faculty: 'Department of ICT', appliedDate: '2025-06-17', documents: ['National ID', 'KCSE Certificate', 'Medical Certificate'], gpa: 3.0, status: 'pending',      nationality: 'Kenyan', email: 'r.ally@student.tich.or.ke', paymentStatus: 'unpaid', amountPaid: 0, registrationFee: 20000 },
  { id: 'APP-2409', studentName: 'Edwin Kipkoech',    studentId: 'STU-2409', program: 'Diploma in Community Health and Development',       faculty: 'Department of Health and Social Sciences', appliedDate: '2025-06-18', documents: ['National ID', 'KCSE Certificate', 'Work Experience Letter'], gpa: 3.3, status: 'approved', reviewedBy: 'Mrs. Grace Mwangi', nationality: 'Kenyan', email: 'e.kipkoech@student.tich.or.ke', paymentStatus: 'paid', amountPaid: 30000, registrationFee: 30000, offerSent: true, offerSentDate: '2025-06-22' },
  { id: 'APP-2410', studentName: 'Naomi Chebet',      studentId: 'STU-2410', program: 'Diploma in Clinical Medicine',     faculty: 'Department of Health and Social Sciences', appliedDate: '2025-06-19', documents: ['National ID', 'A-Level Results', 'Scholarship Letter'], gpa: 3.9, status: 'pending',      nationality: 'Kenyan', email: 'n.chebet@student.tich.or.ke', paymentStatus: 'unpaid', amountPaid: 0, registrationFee: 45000 },
];

export interface Program {
  id: string
  name: string
  department: string
  level: string
  duration: string
  qualification: string
  fee: number
  feeNote?: string
  enrolled: number
  capacity: number
}

export const DEPARTMENTS = [
  'Department of Health and Social Sciences',
  'Department of Catering and Hospitality',
  'Department of Business and Accounting',
  'Department of Data Science and Analytics',
  'Department of Information Communication Technology',
  'Technical Department / Vocational',
  'Academic Registrar',
  'Finance',
  'Human Resources',
]

export const PROGRAMS: Program[] = [
  { id: 'P01', name: 'Community Health and Development', department: 'Department of Health and Social Sciences', level: 'Certificate', duration: '1 Year', qualification: 'D+ in K.C.S.E', fee: 30000, enrolled: 200, capacity: 250 },
  { id: 'P02', name: 'Community Health and Development', department: 'Department of Health and Social Sciences', level: 'Diploma', duration: '2 Years', qualification: 'C- in K.C.S.E', fee: 30000, enrolled: 245, capacity: 300 },
  { id: 'P03', name: 'Community Health and Development (Community College)', department: 'Department of Health and Social Sciences', level: 'Certificate', duration: '1 Year', qualification: 'D+ and Above', fee: 15000, enrolled: 120, capacity: 150 },
  { id: 'P04', name: 'Community Health Nursing', department: 'Department of Health and Social Sciences', level: 'Diploma', duration: '3 Years', qualification: 'C Plain and Above', fee: 45000, enrolled: 160, capacity: 180 },
  { id: 'P05', name: 'Clinical Medicine', department: 'Department of Health and Social Sciences', level: 'Diploma', duration: '3 Years', qualification: 'C in K.C.S.E', fee: 45000, enrolled: 180, capacity: 200 },
  { id: 'P06', name: 'Perioperative Health Technology', department: 'Department of Health and Social Sciences', level: 'Diploma', duration: '3 Years', qualification: 'C in K.C.S.E', fee: 30000, enrolled: 80, capacity: 100 },
  { id: 'P07', name: 'Homecare Management', department: 'Department of Health and Social Sciences', level: 'Artisan', duration: '3 Months', qualification: 'CHPs', fee: 8000, enrolled: 60, capacity: 80 },
  { id: 'P08', name: 'Homecare Management', department: 'Department of Health and Social Sciences', level: 'Certificate', duration: '1 Year', qualification: 'D+ in K.C.S.E', fee: 20000, enrolled: 100, capacity: 130 },
  { id: 'P09', name: 'Homecare Management', department: 'Department of Health and Social Sciences', level: 'Diploma', duration: '2 Years', qualification: 'C- in K.C.S.E', fee: 30000, enrolled: 70, capacity: 90 },
  { id: 'P10', name: 'Health Care Assistant', department: 'Department of Health and Social Sciences', level: 'Artisan', duration: '6 Months', qualification: 'C- in K.C.S.E', fee: 70000, enrolled: 90, capacity: 120 },
  { id: 'P11', name: 'Food and Beverage', department: 'Department of Catering and Hospitality', level: 'Level 4', duration: '1 Year', qualification: 'D- in K.C.S.E', fee: 20000, enrolled: 110, capacity: 140 },
  { id: 'P12', name: 'Food and Beverage', department: 'Department of Catering and Hospitality', level: 'Level 5', duration: '2 Years', qualification: 'D+ in K.C.S.E', fee: 30000, enrolled: 130, capacity: 160 },
  { id: 'P13', name: 'Food and Beverage', department: 'Department of Catering and Hospitality', level: 'Level 6', duration: '3 Years', qualification: 'C- in K.C.S.E', fee: 30000, enrolled: 120, capacity: 150 },
  { id: 'P14', name: 'Certified Public Accountant (C.P.A)', department: 'Department of Business and Accounting', level: 'CPA', duration: '1 Year', qualification: 'C- in K.C.S.E', fee: 25000, enrolled: 150, capacity: 180 },
  { id: 'P15', name: 'Accountant Technician Diploma', department: 'Department of Business and Accounting', level: 'Diploma', duration: '1 Year', qualification: 'D+ in K.C.S.E', fee: 25000, enrolled: 100, capacity: 130 },
  { id: 'P16', name: 'Agribusiness', department: 'Department of Business and Accounting', level: 'Diploma', duration: '3 Years', qualification: 'C- in K.C.S.E', fee: 30000, enrolled: 95, capacity: 120 },
  { id: 'P17', name: 'Agribusiness', department: 'Department of Business and Accounting', level: 'Certificate', duration: '1 Year', qualification: 'D in K.C.S.E', fee: 30000, enrolled: 80, capacity: 100 },
  { id: 'P18', name: 'Data Science', department: 'Department of Data Science and Analytics', level: 'Certificate', duration: '1 Year', qualification: 'D+ in K.C.S.E', fee: 20000, enrolled: 85, capacity: 110 },
  { id: 'P19', name: 'Data Science', department: 'Department of Data Science and Analytics', level: 'Diploma', duration: '2 Years', qualification: 'C- in K.C.S.E', fee: 30000, enrolled: 70, capacity: 90 },
  { id: 'P20', name: 'Information Communication Technology (ICT)', department: 'Department of Information Communication Technology', level: 'Level 4', duration: '1 Year', qualification: 'D- in K.C.S.E', fee: 20000, enrolled: 140, capacity: 170 },
  { id: 'P21', name: 'Information Communication Technology (ICT)', department: 'Department of Information Communication Technology', level: 'Level 5', duration: '2 Years', qualification: 'D- in K.C.S.E', fee: 20000, enrolled: 120, capacity: 150 },
  { id: 'P22', name: 'Information Communication Technology (ICT)', department: 'Department of Information Communication Technology', level: 'Level 6', duration: '3 Years', qualification: 'C- in K.C.S.E', fee: 30000, enrolled: 140, capacity: 160 },
  { id: 'P23', name: 'Computer Repair and Maintenance', department: 'Department of Information Communication Technology', level: 'Artisan', duration: '1 Year', qualification: 'D- in K.C.S.E', fee: 20000, enrolled: 60, capacity: 80 },
  { id: 'P24', name: 'Computer Packages', department: 'Department of Information Communication Technology', level: 'Artisan', duration: '1 Year', qualification: 'D- in K.C.S.E', fee: 4500, feeNote: 'for all packages', enrolled: 200, capacity: 250 },
  { id: 'P25', name: 'Computer Hardware and Maintenance', department: 'Department of Information Communication Technology', level: 'Artisan', duration: '1 Year', qualification: 'D- in K.C.S.E', fee: 20000, enrolled: 55, capacity: 75 },
  { id: 'P26', name: 'Software Engineering', department: 'Department of Information Communication Technology', level: 'Artisan', duration: '1 Year', qualification: 'D- in K.C.S.E', fee: 20000, enrolled: 65, capacity: 85 },
  { id: 'P27', name: 'Computer Network and Security', department: 'Department of Information Communication Technology', level: 'Artisan', duration: '1 Year', qualification: 'D- in K.C.S.E', fee: 20000, enrolled: 50, capacity: 70 },
  { id: 'P28', name: 'System Administration', department: 'Department of Information Communication Technology', level: 'Artisan', duration: '1 Year', qualification: 'D- in K.C.S.E', fee: 20000, enrolled: 45, capacity: 65 },
  { id: 'P29', name: 'Web Design and Development', department: 'Department of Information Communication Technology', level: 'Artisan', duration: '1 Year', qualification: 'D- in K.C.S.E', fee: 20000, enrolled: 70, capacity: 90 },
  { id: 'P30', name: 'Electrical Wireman Installation', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 80, capacity: 100 },
  { id: 'P31', name: 'Masonry', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 60, capacity: 80 },
  { id: 'P32', name: 'Computer Operator', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 90, capacity: 120 },
  { id: 'P33', name: 'Graphics Design', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 75, capacity: 100 },
  { id: 'P34', name: 'Plumbing', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 55, capacity: 75 },
  { id: 'P35', name: 'CCTV Camera Installation', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 40, capacity: 60 },
  { id: 'P36', name: 'Food and Beverage Production', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 85, capacity: 110 },
  { id: 'P37', name: 'Food and Beverage Service', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 70, capacity: 90 },
  { id: 'P38', name: 'Motor Vehicle Mechanics', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 65, capacity: 85 },
  { id: 'P39', name: 'Basic Solar Installation', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 50, capacity: 70 },
  { id: 'P40', name: 'Electrical Filter', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 35, capacity: 55 },
  { id: 'P41', name: 'Motor Cycle Mechanics', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 45, capacity: 65 },
  { id: 'P42', name: 'Motor Vehicle Electrical', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 40, capacity: 60 },
  { id: 'P43', name: 'Motor Vehicle Body Building', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 30, capacity: 50 },
  { id: 'P44', name: 'Spray Painting', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 25, capacity: 45 },
  { id: 'P45', name: 'Refrigeration', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 35, capacity: 55 },
  { id: 'P46', name: 'Welding and Fabrication', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 60, capacity: 80 },
  { id: 'P47', name: 'Motor Rewinding', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 30, capacity: 50 },
  { id: 'P48', name: 'Electronics Mechanics', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 35, capacity: 55 },
  { id: 'P49', name: 'Bio Sanitation', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 40, capacity: 60 },
  { id: 'P50', name: 'Wood Work', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 45, capacity: 65 },
  { id: 'P51', name: 'Hairdressing', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 55, capacity: 75 },
  { id: 'P52', name: 'Beauty Therapy', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 50, capacity: 70 },
  { id: 'P53', name: 'Tailoring and Dressmaking', department: 'Technical Department / Vocational', level: 'NITA Grade 1-3', duration: '6 Months', qualification: 'K.C.P.E and Above', fee: 21000, feeNote: 'per grade', enrolled: 65, capacity: 85 },
]

export const REVENUE_DATA = [
  { month: 'Jan', tuition: 18400000, accommodation: 4200000, other: 1100000 },
  { month: 'Feb', tuition: 16200000, accommodation: 4100000, other: 980000 },
  { month: 'Mar', tuition: 21500000, accommodation: 4300000, other: 1240000 },
  { month: 'Apr', tuition: 19800000, accommodation: 4250000, other: 1050000 },
  { month: 'May', tuition: 22100000, accommodation: 4400000, other: 1320000 },
  { month: 'Jun', tuition: 23400000, accommodation: 4500000, other: 1480000 },
  { month: 'Jul', tuition: 20600000, accommodation: 4350000, other: 1200000 },
];

export const ENROLLMENT_DATA = [
  { year: '2020', students: 892 },
  { year: '2021', students: 1024 },
  { year: '2022', students: 1187 },
  { year: '2023', students: 1354 },
  { year: '2024', students: 1507 },
  { year: '2025', students: 1640 },
];

export const DEPT_PERFORMANCE = [
  { dept: 'Health Sciences',  passRate: 87, avgGPA: 3.2, students: 612 },
  { dept: 'Catering & Hospitality',      passRate: 82, avgGPA: 3.0, students: 467 },
  { dept: 'Business & Accounting',     passRate: 89, avgGPA: 3.4, students: 222 },
  { dept: 'ICT',   passRate: 79, avgGPA: 2.9, students: 87  },
  { dept: 'Data Science',     passRate: 94, avgGPA: 3.7, students: 64  },
];

export const STAFF = [
  { id: 'S01', name: 'Dr. Ruth Wambua',      role: 'Lecturer',       dept: 'Health and Social Sciences',     status: 'active', joined: '2019-03-15', leave: false },
  { id: 'S02', name: 'Mr. Alex Owino',       role: 'Senior Lecturer', dept: 'Catering and Hospitality',     status: 'active', joined: '2018-01-10', leave: false },
  { id: 'S03', name: 'Ms. Bertha Makokha',   role: 'Lecturer',       dept: 'Health and Social Sciences',        status: 'active', joined: '2021-08-01', leave: true  },
  { id: 'S04', name: 'Prof. Ibrahim Juma',   role: 'Professor',      dept: 'Business and Accounting',     status: 'active', joined: '2015-06-20', leave: false },
  { id: 'S05', name: 'Dr. Miriam Akinyi',    role: 'HOD',            dept: 'Health and Social Sciences',      status: 'active', joined: '2017-09-05', leave: false },
  { id: 'S06', name: 'Mr. George Muthomi',   role: 'Tutorial Fellow', dept: 'ICT',    status: 'probation', joined: '2024-02-12', leave: false },
  { id: 'S07', name: 'Ms. Cynthia Otieno',   role: 'Lecturer',       dept: 'Data Science and Analytics',    status: 'active', joined: '2020-11-30', leave: false },
  { id: 'S08', name: 'Mr. Hassan Khamis',    role: 'Lecturer',       dept: 'Catering and Hospitality',     status: 'active', joined: '2022-04-18', leave: false },
];

export const QA_METRICS = [
  { area: 'Curriculum Quality',     score: 88, target: 90, status: 'near' },
  { area: 'Teaching Effectiveness', score: 83, target: 85, status: 'near' },
  { area: 'Student Satisfaction',   score: 91, target: 85, status: 'above' },
  { area: 'Research Output',        score: 67, target: 75, status: 'below' },
  { area: 'Industry Linkages',      score: 79, target: 80, status: 'near' },
  { area: 'Facilities Standard',    score: 85, target: 85, status: 'at' },
];

export const NEWS = [
  { id: 1, title: 'TICH Ranked Among Top Health Training Institutions in Kenya 2025', date: '2025-07-10', category: 'Achievement', excerpt: 'The Tropical Institute of Community Health and Development has been recognised in the 2025 Kenya Health Education rankings for excellence in community health training.' },
  { id: 2, title: 'New Partnership with Ministry of Health Kenya', date: '2025-07-05', category: 'Partnership', excerpt: 'TICH signs MOU with the Ministry of Health to provide clinical attachment opportunities for all diploma and certificate students beginning August 2025.' },
  { id: 3, title: 'Applications Open: September 2025 Intake', date: '2025-06-28', category: 'Admissions', excerpt: 'TICH is now accepting applications for the September 2025 academic intake. Scholarships available for outstanding applicants from across East Africa.' },
  { id: 4, title: 'Annual Community Health Outreach Program 2025', date: '2025-06-20', category: 'Event', excerpt: 'Students and staff conduct free medical camps and health education in rural communities across Kenya.' },
];

export const INSTITUTIONAL_GOALS = [
  { icon: '🎯', title: 'Community Health Impact', description: 'Train competent health professionals who serve underserved communities across Kenya and Africa.' },
  { icon: '🌍', title: 'Pan-African Leadership', description: 'Become the premier centre of excellence for community health and development education in Africa.' },
  { icon: '🤝', title: 'Health System Integration', description: 'Partner with county governments, NGOs, and health institutions for practical training and research.' },
  { icon: '🔬', title: 'Research for Development', description: 'Advance public health knowledge through applied research in tropical diseases and community wellness.' },
  { icon: '🌱', title: 'Sustainable Development', description: 'Promote primary healthcare, preventive medicine, and sustainable community development practices.' },
  { icon: '🎓', title: 'Student Success', description: 'Ensure every graduate is career-ready with practical skills, professional networks, and compassionate care.' },
];

export const FEATURED_PROGRAMS = PROGRAMS.slice(0, 3);

export const UPCOMING_EVENTS = [
  { id: 1, title: 'Open Day & Campus Tour', date: '2025-08-15', time: '09:00 AM', location: 'TICH Main Campus, Nairobi, Kenya', category: 'Admissions' },
  { id: 2, title: 'Annual Health Outreach Camp', date: '2025-08-20', time: '10:00 AM', location: 'Kisumu County, Kenya', category: 'Community' },
  { id: 3, title: 'Career Placement Fair', date: '2025-09-05', time: '08:30 AM', location: 'TICH Grand Hall, Nairobi', category: 'Career' },
  { id: 4, title: 'Public Health Research Symposium', date: '2025-09-18', time: '09:00 AM', location: 'TICH Conference Centre, Nairobi', category: 'Research' },
];

export const RESEARCH_HIGHLIGHTS = [
  { id: 1, title: 'Community Health Worker Models in Rural Kenya', authors: 'Dr. Ruth Wambua, Prof. Ibrahim Juma', date: '2025-06-15', category: 'Public Health', excerpt: 'A comprehensive study on community health worker effectiveness and their impact on maternal and child health outcomes in rural Kenya.' },
  { id: 2, title: 'Digital Health Records in Kenyan Clinics', authors: 'Dr. Samuel Banda, Ms. Cynthia Otieno', date: '2025-05-22', category: 'Health Informatics', excerpt: 'Investigating how mobile health technology is improving patient outcomes and data management in low-resource settings.' },
  { id: 3, title: 'Nutrition and Food Security in Urban Slums', authors: 'Ms. Bertha Makokha, Mr. Hassan Khamis', date: '2025-04-10', category: 'Nutrition', excerpt: 'Mapping food insecurity patterns and developing community-based interventions for sustainable nutrition in Nairobi informal settlements.' },
];

export const BLOG_POSTS = [
  { id: 1, title: 'Why Community Health is Kenya\'s Next Big Frontier', date: '2025-07-12', author: 'TICH Communications', readTime: '5 min', excerpt: 'The growing demand for community health workers is creating unprecedented career opportunities across Kenya and East Africa.' },
  { id: 2, title: 'From Classroom to Clinic: A Student\'s Journey at TICH', date: '2025-07-08', author: 'Grace Mwangi', readTime: '4 min', excerpt: 'Second-year Community Health student shares her experience during the annual rural outreach program in Western Kenya.' },
  { id: 3, title: 'The Future of Primary Healthcare in Kenya', date: '2025-07-01', author: 'Prof. Ibrahim Juma', readTime: '6 min', excerpt: 'How institutions like TICH are leading the transition toward universal health coverage through community-based training.' },
];

export interface Employee {
  id: string
  name: string
  email: string
  department: string
  jobTitle: string
  employmentType: 'permanent' | 'contract' | 'casual' | 'intern'
  hireDate: string
  status: 'active' | 'probation' | 'on_leave' | 'off_boarding'
  salary: number
  lineManager: string
  kraPin: string
  nssfNo: string
  nhifNo: string
  bankName: string
  accountNo: string
  phone: string
  avatar: string
}

export const EMPLOYEES: Employee[] = [
  { id: 'EMP001', name: 'Dr. Ruth Wambua', email: 'r.wambua@tich.or.ke', department: 'Department of Health and Social Sciences', jobTitle: 'Senior Lecturer', employmentType: 'permanent', hireDate: '2019-03-15', status: 'active', salary: 185000, lineManager: 'Prof. Ibrahim Juma', kraPin: 'A123456789X', nssfNo: 'NSSF001', nhifNo: 'NHIF001', bankName: 'Equity Bank', accountNo: '1234567890', phone: '+254 711 222 333', avatar: 'RW' },
  { id: 'EMP002', name: 'Mr. Alex Owino', email: 'a.owino@tich.or.ke', department: 'Department of Catering and Hospitality', jobTitle: 'Senior Lecturer', employmentType: 'permanent', hireDate: '2018-01-10', status: 'active', salary: 165000, lineManager: 'Prof. Ibrahim Juma', kraPin: 'A234567890X', nssfNo: 'NSSF002', nhifNo: 'NHIF002', bankName: 'KCB Bank', accountNo: '2345678901', phone: '+254 722 333 444', avatar: 'AO' },
  { id: 'EMP003', name: 'Ms. Bertha Makokha', email: 'b.makokha@tich.or.ke', department: 'Department of Health and Social Sciences', jobTitle: 'Lecturer', employmentType: 'permanent', hireDate: '2021-08-01', status: 'on_leave', salary: 145000, lineManager: 'Dr. Ruth Wambua', kraPin: 'B345678901X', nssfNo: 'NSSF003', nhifNo: 'NHIF003', bankName: 'Equity Bank', accountNo: '3456789012', phone: '+254 733 444 555', avatar: 'BM' },
  { id: 'EMP004', name: 'Prof. Ibrahim Juma', email: 'i.juma@tich.or.ke', department: 'Department of Business and Accounting', jobTitle: 'Professor', employmentType: 'permanent', hireDate: '2015-06-20', status: 'active', salary: 220000, lineManager: 'Dr. Amara Osei', kraPin: 'C456789012X', nssfNo: 'NSSF004', nhifNo: 'NHIF004', bankName: 'Stanbic Bank', accountNo: '4567890123', phone: '+254 744 555 666', avatar: 'IJ' },
  { id: 'EMP005', name: 'Dr. Miriam Akinyi', email: 'm.akinyi@tich.or.ke', department: 'Department of Health and Social Sciences', jobTitle: 'HOD', employmentType: 'permanent', hireDate: '2017-09-05', status: 'active', salary: 195000, lineManager: 'Prof. Ibrahim Juma', kraPin: 'D567890123X', nssfNo: 'NSSF005', nhifNo: 'NHIF005', bankName: 'Equity Bank', accountNo: '5678901234', phone: '+254 755 666 777', avatar: 'MA' },
  { id: 'EMP006', name: 'Mr. George Muthomi', email: 'g.muthomi@tich.or.ke', department: 'Department of Information Communication Technology', jobTitle: 'Tutorial Fellow', employmentType: 'contract', hireDate: '2024-02-12', status: 'probation', salary: 95000, lineManager: 'Mr. Hassan Khamis', kraPin: 'E678901234X', nssfNo: 'NSSF006', nhifNo: 'NHIF006', bankName: 'KCB Bank', accountNo: '6789012345', phone: '+254 766 777 888', avatar: 'GM' },
  { id: 'EMP007', name: 'Ms. Cynthia Otieno', email: 'c.otieno@tich.or.ke', department: 'Department of Data Science and Analytics', jobTitle: 'Lecturer', employmentType: 'permanent', hireDate: '2020-11-30', status: 'active', salary: 145000, lineManager: 'Prof. Ibrahim Juma', kraPin: 'F789012345X', nssfNo: 'NSSF007', nhifNo: 'NHIF007', bankName: 'Co-op Bank', accountNo: '7890123456', phone: '+254 777 888 999', avatar: 'CO' },
  { id: 'EMP008', name: 'Mr. Hassan Khamis', email: 'h.khamis@tich.or.ke', department: 'Department of Catering and Hospitality', jobTitle: 'Lecturer', employmentType: 'permanent', hireDate: '2022-04-18', status: 'active', salary: 140000, lineManager: 'Prof. Ibrahim Juma', kraPin: 'G890123456X', nssfNo: 'NSSF008', nhifNo: 'NHIF008', bankName: 'Equity Bank', accountNo: '8901234567', phone: '+254 788 999 000', avatar: 'HK' },
  { id: 'EMP009', name: 'Mrs. Grace Mwangi', email: 'g.mwangi@tich.or.ke', department: 'Academic Registrar', jobTitle: 'Academic Registrar', employmentType: 'permanent', hireDate: '2018-01-10', status: 'active', salary: 175000, lineManager: 'Dr. Fatima Nkrumah', kraPin: 'H901234567X', nssfNo: 'NSSF009', nhifNo: 'NHIF009', bankName: 'KCB Bank', accountNo: '9012345678', phone: '+254 799 000 111', avatar: 'GM' },
  { id: 'EMP010', name: 'Mr. David Kamau', email: 'd.kamau@tich.or.ke', department: 'Finance', jobTitle: 'Finance Manager', employmentType: 'permanent', hireDate: '2019-05-20', status: 'active', salary: 180000, lineManager: 'Prof. James Kariuki', kraPin: 'J012345678X', nssfNo: 'NSSF010', nhifNo: 'NHIF010', bankName: 'Stanbic Bank', accountNo: '0123456789', phone: '+254 700 111 222', avatar: 'DK' },
]

export interface LeaveRecord {
  id: string
  employeeId: string
  employeeName: string
  leaveType: 'annual' | 'sick' | 'maternity' | 'paternity' | 'study' | 'compassionate'
  startDate: string
  endDate: string
  days: number
  status: 'pending' | 'approved' | 'rejected' | 'cancelled'
  approvedBy?: string
  reason: string
  reliever?: string
}

export const LEAVE_RECORDS: LeaveRecord[] = [
  { id: 'LV001', employeeId: 'EMP003', employeeName: 'Ms. Bertha Makokha', leaveType: 'annual', startDate: '2025-07-14', endDate: '2025-07-28', days: 10, status: 'approved', approvedBy: 'Dr. Ruth Wambua', reason: 'Family vacation', reliever: 'Mr. Hassan Khamis' },
  { id: 'LV002', employeeId: 'EMP002', employeeName: 'Mr. Alex Owino', leaveType: 'sick', startDate: '2025-07-20', endDate: '2025-07-22', days: 3, status: 'pending', reason: 'Malaria', reliever: '' },
  { id: 'LV003', employeeId: 'EMP005', employeeName: 'Dr. Miriam Akinyi', leaveType: 'maternity', startDate: '2025-08-01', endDate: '2025-10-29', days: 90, status: 'approved', approvedBy: 'Prof. Ibrahim Juma', reason: 'Maternity leave', reliever: 'Ms. Cynthia Otieno' },
  { id: 'LV004', employeeId: 'EMP007', employeeName: 'Ms. Cynthia Otieno', leaveType: 'annual', startDate: '2025-08-05', endDate: '2025-08-12', days: 7, status: 'pending', reason: 'Personal leave', reliever: '' },
  { id: 'LV005', employeeId: 'EMP001', employeeName: 'Dr. Ruth Wambua', leaveType: 'sick', startDate: '2025-07-10', endDate: '2025-07-11', days: 2, status: 'approved', approvedBy: 'Prof. Ibrahim Juma', reason: 'Flu', reliever: 'Ms. Bertha Makokha' },
]

export interface PayrollRecord {
  id: string
  employeeId: string
  employeeName: string
  department: string
  basicSalary: number
  allowances: number
  grossSalary: number
  paye: number
  nssf: number
  nhif: number
  housingLevy: number
  otherDeductions: number
  netPay: number
  paymentMethod: 'bank' | 'mpesa' | 'cheque'
  status: 'pending' | 'processed' | 'paid' | 'failed'
  period: string
}

export const PAYROLL_RECORDS: PayrollRecord[] = [
  { id: 'PAY001', employeeId: 'EMP001', employeeName: 'Dr. Ruth Wambua', department: 'Department of Health and Social Sciences', basicSalary: 185000, allowances: 25000, grossSalary: 210000, paye: 42000, nssf: 360, nhif: 1700, housingLevy: 3150, otherDeductions: 5000, netPay: 157790, paymentMethod: 'bank', status: 'paid', period: '2025-07' },
  { id: 'PAY002', employeeId: 'EMP002', employeeName: 'Mr. Alex Owino', department: 'Department of Catering and Hospitality', basicSalary: 165000, allowances: 20000, grossSalary: 185000, paye: 35000, nssf: 360, nhif: 1700, housingLevy: 2775, otherDeductions: 3000, netPay: 143165, paymentMethod: 'mpesa', status: 'paid', period: '2025-07' },
  { id: 'PAY003', employeeId: 'EMP003', employeeName: 'Ms. Bertha Makokha', department: 'Department of Health and Social Sciences', basicSalary: 145000, allowances: 15000, grossSalary: 160000, paye: 28000, nssf: 360, nhif: 1700, housingLevy: 2400, otherDeductions: 2000, netPay: 125540, paymentMethod: 'bank', status: 'paid', period: '2025-07' },
  { id: 'PAY004', employeeId: 'EMP004', employeeName: 'Prof. Ibrahim Juma', department: 'Department of Business and Accounting', basicSalary: 220000, allowances: 35000, grossSalary: 255000, paye: 55000, nssf: 360, nhif: 1700, housingLevy: 3825, otherDeductions: 8000, netPay: 187115, paymentMethod: 'bank', status: 'paid', period: '2025-07' },
  { id: 'PAY005', employeeId: 'EMP005', employeeName: 'Dr. Miriam Akinyi', department: 'Department of Health and Social Sciences', basicSalary: 195000, allowances: 25000, grossSalary: 220000, paye: 46000, nssf: 360, nhif: 1700, housingLevy: 3300, otherDeductions: 5000, netPay: 164640, paymentMethod: 'bank', status: 'paid', period: '2025-07' },
  { id: 'PAY006', employeeId: 'EMP006', employeeName: 'Mr. George Muthomi', department: 'Department of Information Communication Technology', basicSalary: 95000, allowances: 10000, grossSalary: 105000, paye: 14000, nssf: 360, nhif: 600, housingLevy: 1575, otherDeductions: 0, netPay: 88465, paymentMethod: 'mpesa', status: 'paid', period: '2025-07' },
  { id: 'PAY007', employeeId: 'EMP007', employeeName: 'Ms. Cynthia Otieno', department: 'Department of Data Science and Analytics', basicSalary: 145000, allowances: 15000, grossSalary: 160000, paye: 28000, nssf: 360, nhif: 1700, housingLevy: 2400, otherDeductions: 2000, netPay: 125540, paymentMethod: 'bank', status: 'paid', period: '2025-07' },
  { id: 'PAY008', employeeId: 'EMP008', employeeName: 'Mr. Hassan Khamis', department: 'Department of Catering and Hospitality', basicSalary: 140000, allowances: 15000, grossSalary: 155000, paye: 26000, nssf: 360, nhif: 1700, housingLevy: 2325, otherDeductions: 2000, netPay: 122615, paymentMethod: 'bank', status: 'paid', period: '2025-07' },
]

export interface AttendanceRecord {
  id: string
  employeeId: string
  employeeName: string
  date: string
  clockIn: string
  clockOut: string
  hoursWorked: number
  overtime: number
  status: 'present' | 'absent' | 'late' | 'half_day' | 'leave'
  notes?: string
}

export const ATTENDANCE_RECORDS: AttendanceRecord[] = [
  { id: 'ATT001', employeeId: 'EMP001', employeeName: 'Dr. Ruth Wambua', date: '2025-07-21', clockIn: '08:00', clockOut: '17:00', hoursWorked: 8, overtime: 1, status: 'present', notes: 'Extra hour for departmental meeting' },
  { id: 'ATT002', employeeId: 'EMP002', employeeName: 'Mr. Alex Owino', date: '2025-07-21', clockIn: '08:15', clockOut: '17:00', hoursWorked: 7.75, overtime: 0, status: 'late', notes: '15 minutes late' },
  { id: 'ATT003', employeeId: 'EMP003', employeeName: 'Ms. Bertha Makokha', date: '2025-07-21', clockIn: '--:--', clockOut: '--:--', hoursWorked: 0, overtime: 0, status: 'leave', notes: 'On approved annual leave' },
  { id: 'ATT004', employeeId: 'EMP004', employeeName: 'Prof. Ibrahim Juma', date: '2025-07-21', clockIn: '07:45', clockOut: '17:30', hoursWorked: 9.25, overtime: 1.25, status: 'present', notes: '' },
  { id: 'ATT005', employeeId: 'EMP005', employeeName: 'Dr. Miriam Akinyi', date: '2025-07-21', clockIn: '08:00', clockOut: '17:00', hoursWorked: 8, overtime: 0, status: 'present', notes: '' },
  { id: 'ATT006', employeeId: 'EMP006', employeeName: 'Mr. George Muthomi', date: '2025-07-21', clockIn: '08:30', clockOut: '17:00', hoursWorked: 7.5, overtime: 0, status: 'late', notes: '30 minutes late' },
  { id: 'ATT007', employeeId: 'EMP007', employeeName: 'Ms. Cynthia Otieno', date: '2025-07-21', clockIn: '08:00', clockOut: '17:00', hoursWorked: 8, overtime: 0, status: 'present', notes: '' },
  { id: 'ATT008', employeeId: 'EMP008', employeeName: 'Mr. Hassan Khamis', date: '2025-07-21', clockIn: '08:05', clockOut: '17:00', hoursWorked: 7.75, overtime: 0, status: 'late', notes: '5 minutes late' },
]

export interface DocumentRecord {
  id: string
  employeeId: string
  employeeName: string
  category: string
  title: string
  uploadDate: string
  size: string
  type: 'pdf' | 'image' | 'doc'
}

export const DOCUMENTS: DocumentRecord[] = [
  { id: 'DOC001', employeeId: 'EMP001', employeeName: 'Dr. Ruth Wambua', category: 'ID Documents', title: 'National ID - Ruth Wambua.pdf', uploadDate: '2025-01-15', size: '245 KB', type: 'pdf' },
  { id: 'DOC002', employeeId: 'EMP001', employeeName: 'Dr. Ruth Wambua', category: 'Certificates', title: 'PhD Certificate - Education.pdf', uploadDate: '2025-01-15', size: '1.2 MB', type: 'pdf' },
  { id: 'DOC003', employeeId: 'EMP002', employeeName: 'Mr. Alex Owino', category: 'ID Documents', title: 'National ID - Alex Owino.pdf', uploadDate: '2025-01-20', size: '230 KB', type: 'pdf' },
  { id: 'DOC004', employeeId: 'EMP003', employeeName: 'Ms. Bertha Makokha', category: 'Contracts', title: 'Employment Contract 2021.pdf', uploadDate: '2021-08-01', size: '560 KB', type: 'pdf' },
  { id: 'DOC005', employeeId: 'EMP004', employeeName: 'Prof. Ibrahim Juma', category: 'Certificates', title: 'Professor Appointment Letter.pdf', uploadDate: '2015-06-20', size: '890 KB', type: 'pdf' },
  { id: 'DOC006', employeeId: 'EMP005', employeeName: 'Dr. Miriam Akinyi', category: 'ID Documents', title: 'National ID - Miriam Akinyi.pdf', uploadDate: '2017-09-05', size: '220 KB', type: 'pdf' },
  { id: 'DOC007', employeeId: 'EMP006', employeeName: 'Mr. George Muthomi', category: 'Contracts', title: 'Contract - Tutorial Fellow 2024.pdf', uploadDate: '2024-02-12', size: '450 KB', type: 'pdf' },
  { id: 'DOC008', employeeId: 'EMP007', employeeName: 'Ms. Cynthia Otieno', category: 'Certificates', title: 'MSc Data Science Certificate.pdf', uploadDate: '2020-11-30', size: '780 KB', type: 'pdf' },
]

export interface StatutoryReport {
  id: string
  name: string
  description: string
  period: string
  status: 'ready' | 'pending' | 'filed'
}

export const STATUTORY_REPORTS: StatutoryReport[] = [
  { id: 'RPT001', name: 'PAYE Return', description: 'Pay-As-You-Earn tax return to KRA', period: '2025-07', status: 'ready' },
  { id: 'RPT002', name: 'NSSF Return', description: 'National Social Security Fund contributions', period: '2025-07', status: 'ready' },
  { id: 'RPT003', name: 'SHIF/NHIF Return', description: 'Social Health Insurance Fund contributions', period: '2025-07', status: 'pending' },
  { id: 'RPT004', name: 'NITA Return', description: 'National Industrial Training Authority levy', period: '2025-07', status: 'ready' },
  { id: 'RPT005', name: 'Housing Levy', description: '1.5% Affordable Housing Levy', period: '2025-07', status: 'ready' },
  { id: 'RPT006', name: 'P9 Forms', description: 'Annual tax certificates per employee', period: '2024', status: 'pending' },
]

export interface ExpenseClaim {
  id: string
  employeeId: string
  employeeName: string
  category: string
  amount: number
  date: string
  status: 'pending' | 'approved' | 'rejected' | 'paid'
  approvedBy?: string
  receipt?: string
}

export const EXPENSE_CLAIMS: ExpenseClaim[] = [
  { id: 'EXP001', employeeId: 'EMP001', employeeName: 'Dr. Ruth Wambua', category: 'Transport', amount: 8500, date: '2025-07-18', status: 'paid', approvedBy: 'Mr. David Kamau', receipt: 'receipt_001.pdf' },
  { id: 'EXP002', employeeId: 'EMP002', employeeName: 'Mr. Alex Owino', category: 'Accommodation', amount: 15000, date: '2025-07-19', status: 'approved', approvedBy: 'Mr. David Kamau', receipt: 'receipt_002.pdf' },
  { id: 'EXP003', employeeId: 'EMP004', employeeName: 'Prof. Ibrahim Juma', category: 'Conference', amount: 45000, date: '2025-07-20', status: 'pending', receipt: 'receipt_003.pdf' },
  { id: 'EXP004', employeeId: 'EMP007', employeeName: 'Ms. Cynthia Otieno', category: 'Training', amount: 12000, date: '2025-07-21', status: 'pending', receipt: '' },
]

export interface Invoice {
  id: string
  invoiceNo: string
  customerName: string
  studentName?: string
  program?: string
  amount: number
  tax: number
  total: number
  issueDate: string
  dueDate: string
  status: 'draft' | 'sent' | 'paid' | 'overdue' | 'void'
  paymentMethod?: string
}

export const INVOICES: Invoice[] = [
  { id: 'INV001', invoiceNo: 'TICH-2025-001', customerName: 'Brian Otieno', studentName: 'Brian Otieno', program: 'Diploma in Community Health and Development', amount: 120000, tax: 0, total: 120000, issueDate: '2025-07-01', dueDate: '2025-07-15', status: 'paid', paymentMethod: 'M-PESA' },
  { id: 'INV002', invoiceNo: 'TICH-2025-002', customerName: 'Amina Hassan', studentName: 'Amina Hassan', program: 'Diploma in Clinical Medicine', amount: 270000, tax: 0, total: 270000, issueDate: '2025-07-01', dueDate: '2025-07-15', status: 'paid', paymentMethod: 'Bank Transfer' },
  { id: 'INV003', invoiceNo: 'TICH-2025-003', customerName: 'John Mwangi', studentName: 'John Mwangi', program: 'Certificate in Community Health and Development', amount: 60000, tax: 0, total: 60000, issueDate: '2025-07-05', dueDate: '2025-07-19', status: 'overdue' },
  { id: 'INV004', invoiceNo: 'TICH-2025-004', customerName: 'Fatuma Salim', studentName: 'Fatuma Salim', program: 'Diploma in Food and Beverage (Level 6)', amount: 180000, tax: 0, total: 180000, issueDate: '2025-07-10', dueDate: '2025-07-24', status: 'sent' },
  { id: 'INV005', invoiceNo: 'TICH-2025-005', customerName: 'Kevin Njoroge', studentName: 'Kevin Njoroge', program: 'Diploma in Agribusiness', amount: 120000, tax: 0, total: 120000, issueDate: '2025-07-12', dueDate: '2025-07-26', status: 'sent' },
]

export interface Bill {
  id: string
  billNo: string
  vendorName: string
  category: string
  amount: number
  dueDate: string
  status: 'draft' | 'pending' | 'paid' | 'overdue'
  approvedBy?: string
}

export const BILLS: Bill[] = [
  { id: 'BIL001', billNo: 'TICH-B-2025-001', vendorName: 'Kenya Power', category: 'Utilities', amount: 450000, dueDate: '2025-07-25', status: 'pending' },
  { id: 'BIL002', billNo: 'TICH-B-2025-002', vendorName: 'Safaricom Business', category: 'Telecommunications', amount: 120000, dueDate: '2025-07-20', status: 'overdue' },
  { id: 'BIL003', billNo: 'TICH-B-2025-003', vendorName: 'Ministry of Health', category: 'Grants Received', amount: 2500000, dueDate: '2025-07-30', status: 'paid', approvedBy: 'Mr. David Kamau' },
  { id: 'BIL004', billNo: 'TICH-B-2025-004', vendorName: 'Equity Bank', category: 'Loan Repayment', amount: 180000, dueDate: '2025-07-28', status: 'pending' },
]

export interface BankTransaction {
  id: string
  date: string
  description: string
  category: string
  amount: number
  type: 'debit' | 'credit'
  balance: number
  reference: string
  reconciled: boolean
}

export const BANK_TRANSACTIONS: BankTransaction[] = [
  { id: 'BNK001', date: '2025-07-21', description: 'M-PESA Bulk Payout - Staff Salaries', category: 'Payroll', amount: 2450000, type: 'debit', balance: 12500000, reference: 'MPB250721001', reconciled: true },
  { id: 'BNK002', date: '2025-07-21', description: 'Student Fee Payment - Brian Otieno', category: 'Tuition', amount: 120000, type: 'credit', balance: 14950000, reference: 'BNK220721002', reconciled: true },
  { id: 'BNK003', date: '2025-07-20', description: 'Utility Payment - Kenya Power', category: 'Utilities', amount: 450000, type: 'debit', balance: 14830000, reference: 'BNK220720003', reconciled: false },
  { id: 'BNK004', date: '2025-07-20', description: 'Student Fee Payment - Amina Hassan', category: 'Tuition', amount: 270000, type: 'credit', balance: 15280000, reference: 'BNK220720004', reconciled: true },
  { id: 'BNK005', date: '2025-07-19', description: 'Grant Receipt - Ministry of Health', category: 'Grants', amount: 2500000, type: 'credit', balance: 15010000, reference: 'BNK220719005', reconciled: false },
  { id: 'BNK006', date: '2025-07-18', description: 'Office Supplies Purchase', category: 'Supplies', amount: 85000, type: 'debit', balance: 12510000, reference: 'BNK220718006', reconciled: false },
]

export interface Account {
  id: string
  code: string
  name: string
  type: 'asset' | 'liability' | 'equity' | 'revenue' | 'expense'
  balance: number
  parent?: string
}

export const CHART_OF_ACCOUNTS: Account[] = [
  { id: 'ACC001', code: '1000', name: 'Cash and Cash Equivalents', type: 'asset', balance: 12500000 },
  { id: 'ACC002', code: '1100', name: 'Accounts Receivable', type: 'asset', balance: 3500000 },
  { id: 'ACC003', code: '1200', name: 'Inventory', type: 'asset', balance: 1200000 },
  { id: 'ACC004', code: '1500', name: 'Fixed Assets', type: 'asset', balance: 85000000 },
  { id: 'ACC005', code: '2000', name: 'Accounts Payable', type: 'liability', balance: 2100000 },
  { id: 'ACC006', code: '2100', name: 'Accrued Liabilities', type: 'liability', balance: 1800000 },
  { id: 'ACC007', code: '2200', name: 'Student Deposits', type: 'liability', balance: 950000 },
  { id: 'ACC008', code: '3000', name: 'Retained Earnings', type: 'equity', balance: 45000000 },
  { id: 'ACC009', code: '4000', name: 'Tuition Revenue', type: 'revenue', balance: 28500000 },
  { id: 'ACC010', code: '4100', name: 'Accommodation Revenue', type: 'revenue', balance: 5200000 },
  { id: 'ACC011', code: '4200', name: 'Other Revenue', type: 'revenue', balance: 2100000 },
  { id: 'ACC012', code: '5000', name: 'Staff Salaries', type: 'expense', balance: 61200000 },
  { id: 'ACC013', code: '5100', name: 'Utilities', type: 'expense', balance: 4800000 },
  { id: 'ACC014', code: '5200', name: 'Supplies', type: 'expense', balance: 2100000 },
  { id: 'ACC015', code: '5300', name: 'Maintenance', type: 'expense', balance: 3500000 },
  { id: 'ACC016', code: '5400', name: 'Marketing', type: 'expense', balance: 3200000 },
]

export interface Vendor {
  id: string
  name: string
  category: string
  email: string
  phone: string
  totalPaid: number
  outstanding: number
  paymentStatus: 'paid' | 'pending' | 'in_review'
  status: 'active' | 'inactive'
}

export const VENDORS: Vendor[] = [
  { id: 'VND001', name: 'Kenya Power', category: 'Utilities', email: 'info@kplc.co.ke', phone: '+254 20 3200000', totalPaid: 5400000, outstanding: 450000, paymentStatus: 'pending', status: 'active' },
  { id: 'VND002', name: 'Safaricom Business', category: 'Telecommunications', email: 'business@safaricom.co.ke', phone: '+254 722 001000', totalPaid: 1200000, outstanding: 120000, paymentStatus: 'in_review', status: 'active' },
  { id: 'VND003', name: 'Ministry of Health', category: 'Grants', email: 'info@health.go.ke', phone: '+254 20 2717000', totalPaid: 2500000, outstanding: 0, paymentStatus: 'paid', status: 'active' },
  { id: 'VND004', name: 'Equity Bank', category: 'Banking', email: 'info@equitybank.co.ke', phone: '+254 20 2800000', totalPaid: 3600000, outstanding: 180000, paymentStatus: 'pending', status: 'active' },
  { id: 'VND005', name: 'Office Supplies Ltd', category: 'Supplies', email: 'info@officesupplies.co.ke', phone: '+254 733 600000', totalPaid: 850000, outstanding: 0, paymentStatus: 'paid', status: 'inactive' },
]

export interface Payment {
  id: string
  date: string
  amount: number
  method: 'M-PESA' | 'Bank Transfer' | 'Cheque' | 'Cash'
  reference: string
  category: 'Tuition' | 'Accommodation' | 'Registration' | 'Late Payment' | 'Other'
  status: 'completed' | 'pending' | 'failed'
  receivedBy: string
  notes?: string
}

export interface Customer {
  id: string
  name: string
  email: string
  phone: string
  studentId: string
  program: string
  period: string
  totalFee: number
  totalPaid: number
  outstanding: number
  status: 'active' | 'inactive'
  admissionDate: string
  yearOfStudy: number
  semester: string
  payments: Payment[]
}

export const CUSTOMERS: Customer[] = [
  {
    id: 'CUS001', name: 'Brian Otieno', email: 'b.otieno@student.tich.or.ke', phone: '+254 711 111 111',
    studentId: 'STU-2401', program: 'Diploma in Community Health and Development', period: '2025/2026 - Year 1',
    totalFee: 120000, totalPaid: 120000, outstanding: 0, status: 'active',
    admissionDate: '2025-09-01', yearOfStudy: 1, semester: 'Semester 1',
    payments: [
      { id: 'PAY001', date: '2025-07-01', amount: 60000, method: 'M-PESA', reference: 'MPB250701001', category: 'Tuition', status: 'completed', receivedBy: 'Finance Office' },
      { id: 'PAY002', date: '2025-07-15', amount: 60000, method: 'Bank Transfer', reference: 'BNK220715002', category: 'Tuition', status: 'completed', receivedBy: 'Finance Office' },
    ]
  },
  {
    id: 'CUS002', name: 'Amina Hassan', email: 'a.hassan@student.tich.or.ke', phone: '+254 722 222 222',
    studentId: 'STU-2402', program: 'Diploma in Clinical Medicine', period: '2025/2026 - Year 1',
    totalFee: 270000, totalPaid: 180000, outstanding: 90000, status: 'active',
    admissionDate: '2025-09-01', yearOfStudy: 1, semester: 'Semester 1',
    payments: [
      { id: 'PAY003', date: '2025-07-01', amount: 180000, method: 'Bank Transfer', reference: 'BNK220720004', category: 'Tuition', status: 'completed', receivedBy: 'Finance Office' },
    ]
  },
  {
    id: 'CUS003', name: 'John Mwangi', email: 'j.mwangi@student.tich.or.ke', phone: '+254 733 333 333',
    studentId: 'STU-2403', program: 'Certificate in Community Health and Development', period: '2025/2026 - Year 1',
    totalFee: 60000, totalPaid: 0, outstanding: 60000, status: 'active',
    admissionDate: '2025-09-01', yearOfStudy: 1, semester: 'Semester 1',
    payments: []
  },
  {
    id: 'CUS004', name: 'Fatuma Salim', email: 'f.salim@student.tich.or.ke', phone: '+254 744 444 444',
    studentId: 'STU-2404', program: 'Diploma in Food and Beverage (Level 6)', period: '2025/2026 - Year 1',
    totalFee: 180000, totalPaid: 90000, outstanding: 90000, status: 'active',
    admissionDate: '2025-09-01', yearOfStudy: 1, semester: 'Semester 1',
    payments: [
      { id: 'PAY004', date: '2025-07-05', amount: 90000, method: 'M-PESA', reference: 'MPB250705003', category: 'Tuition', status: 'completed', receivedBy: 'Finance Office' },
    ]
  },
  {
    id: 'CUS005', name: 'Kevin Njoroge', email: 'k.njoroge@student.tich.or.ke', phone: '+254 755 555 555',
    studentId: 'STU-2405', program: 'Diploma in Agribusiness', period: '2025/2026 - Year 1',
    totalFee: 120000, totalPaid: 120000, outstanding: 0, status: 'active',
    admissionDate: '2025-09-01', yearOfStudy: 1, semester: 'Semester 1',
    payments: [
      { id: 'PAY005', date: '2025-07-10', amount: 120000, method: 'M-PESA', reference: 'MPB250710004', category: 'Tuition', status: 'completed', receivedBy: 'Finance Office' },
    ]
  },
  {
    id: 'CUS006', name: 'Zuwena Kombo', email: 'z.kombo@student.tich.or.ke', phone: '+254 766 666 666',
    studentId: 'STU-2406', program: 'Diploma in Data Science', period: '2025/2026 - Year 2',
    totalFee: 300000, totalPaid: 210000, outstanding: 90000, status: 'active',
    admissionDate: '2024-09-01', yearOfStudy: 2, semester: 'Semester 2',
    payments: [
      { id: 'PAY006', date: '2024-09-01', amount: 150000, method: 'Bank Transfer', reference: 'BNK220240901', category: 'Tuition', status: 'completed', receivedBy: 'Finance Office' },
      { id: 'PAY007', date: '2025-01-15', amount: 60000, method: 'M-PESA', reference: 'MPB250115001', category: 'Tuition', status: 'completed', receivedBy: 'Finance Office' },
      { id: 'PAY008', date: '2025-05-20', amount: 60000, method: 'M-PESA', reference: 'MPB250520002', category: 'Late Payment', status: 'completed', receivedBy: 'Finance Office', notes: 'Late payment fee included' },
    ]
  },
  {
    id: 'CUS007', name: 'Moses Auma', email: 'm.auma@student.tich.or.ke', phone: '+254 777 777 777',
    studentId: 'STU-2407', program: 'Diploma in ICT', period: '2024/2025 - Year 1',
    totalFee: 180000, totalPaid: 60000, outstanding: 120000, status: 'inactive',
    admissionDate: '2024-09-01', yearOfStudy: 1, semester: 'Semester 2',
    payments: [
      { id: 'PAY009', date: '2024-09-01', amount: 60000, method: 'M-PESA', reference: 'MPB240901001', category: 'Tuition', status: 'completed', receivedBy: 'Finance Office' },
    ]
  },
]

export interface JournalEntry {
  id: string
  date: string
  account: string
  description: string
  debit: number
  credit: number
  reference: string
}

export const JOURNAL_ENTRIES: JournalEntry[] = [
  { id: 'JNL001', date: '2025-07-21', account: 'Cash and Cash Equivalents', description: 'Student fee payment - Brian Otieno', debit: 120000, credit: 0, reference: 'INV-001' },
  { id: 'JNL002', date: '2025-07-21', account: 'Tuition Revenue', description: 'Student fee payment - Brian Otieno', debit: 0, credit: 120000, reference: 'INV-001' },
  { id: 'JNL003', date: '2025-07-21', account: 'Bank Account', description: 'M-PESA Bulk Payout - Staff Salaries', debit: 0, credit: 2450000, reference: 'PAY-001' },
  { id: 'JNL004', date: '2025-07-21', account: 'Staff Salaries', description: 'M-PESA Bulk Payout - Staff Salaries', debit: 2450000, credit: 0, reference: 'PAY-001' },
  { id: 'JNL005', date: '2025-07-20', account: 'Cash and Cash Equivalents', description: 'Student fee payment - Amina Hassan', debit: 270000, credit: 0, reference: 'INV-002' },
  { id: 'JNL006', date: '2025-07-20', account: 'Tuition Revenue', description: 'Student fee payment - Amina Hassan', debit: 0, credit: 270000, reference: 'INV-002' },
]

export interface Course {
  id: string
  code: string
  name: string
  department: string
  level: string
  duration: string
  credits: number
  totalFee: number
  enrolled: number
  capacity: number
  instructor: string
  schedule: string
  status: 'active' | 'upcoming' | 'completed'
}

export const COURSES: Course[] = [
  { id: 'CRS001', code: 'CHD 201', name: 'Community Health Fundamentals', department: 'Department of Health and Social Sciences', level: 'Certificate', duration: '1 Year', credits: 12, totalFee: 30000, enrolled: 45, capacity: 50, instructor: 'Dr. Ruth Wambua', schedule: 'Mon-Wed-Fri 08:00-10:00', status: 'active' },
  { id: 'CRS002', code: 'CLM 301', name: 'Clinical Medicine Principles', department: 'Department of Health and Social Sciences', level: 'Diploma', duration: '3 Years', credits: 15, totalFee: 45000, enrolled: 38, capacity: 40, instructor: 'Prof. Ibrahim Juma', schedule: 'Tue-Thu 08:00-11:00', status: 'active' },
  { id: 'CRS003', code: 'F&B 201', name: 'Food and Beverage Operations', department: 'Department of Catering and Hospitality', level: 'Level 5', duration: '2 Years', credits: 10, totalFee: 30000, enrolled: 32, capacity: 40, instructor: 'Mr. Alex Owino', schedule: 'Mon-Wed 10:00-12:00', status: 'active' },
  { id: 'CRS004', code: 'AGR 101', name: 'Introduction to Agribusiness', department: 'Department of Business and Accounting', level: 'Diploma', duration: '3 Years', credits: 8, totalFee: 30000, enrolled: 28, capacity: 35, instructor: 'Dr. Miriam Akinyi', schedule: 'Tue-Thu 14:00-16:00', status: 'active' },
  { id: 'CRS005', code: 'ICT 201', name: 'Computer Applications', department: 'Department of Information Communication Technology', level: 'Level 4', duration: '1 Year', credits: 10, totalFee: 20000, enrolled: 50, capacity: 55, instructor: 'Mr. George Muthomi', schedule: 'Mon-Fri 10:00-12:00', status: 'active' },
  { id: 'CRS006', code: 'DAT 301', name: 'Data Analytics Fundamentals', department: 'Department of Data Science and Analytics', level: 'Certificate', duration: '1 Year', credits: 12, totalFee: 20000, enrolled: 22, capacity: 30, instructor: 'Ms. Cynthia Otieno', schedule: 'Wed-Fri 14:00-16:00', status: 'upcoming' },
]

export interface Exam {
  id: string
  courseCode: string
  courseName: string
  instructor: string
  date: string
  time: string
  venue: string
  duration: string
  type: 'CAT' | 'Final' | 'Supplementary'
  status: 'scheduled' | 'completed' | 'postponed'
}

export const EXAMS: Exam[] = [
  { id: 'EXM001', courseCode: 'CHD 201', courseName: 'Community Health Fundamentals', instructor: 'Dr. Ruth Wambua', date: '2025-08-15', time: '09:00-11:00', venue: 'Hall A', duration: '2 hours', type: 'CAT', status: 'scheduled' },
  { id: 'EXM002', courseCode: 'CLM 301', courseName: 'Clinical Medicine Principles', instructor: 'Prof. Ibrahim Juma', date: '2025-08-18', time: '09:00-12:00', venue: 'Hall B', duration: '3 hours', type: 'Final', status: 'scheduled' },
  { id: 'EXM003', courseCode: 'F&B 201', courseName: 'Food and Beverage Operations', instructor: 'Mr. Alex Owino', date: '2025-08-12', time: '14:00-16:00', venue: 'Lab 1', duration: '2 hours', type: 'CAT', status: 'completed' },
  { id: 'EXM004', courseCode: 'ICT 201', courseName: 'Computer Applications', instructor: 'Mr. George Muthomi', date: '2025-08-20', time: '10:00-12:00', venue: 'Computer Lab', duration: '2 hours', type: 'Final', status: 'scheduled' },
  { id: 'EXM005', courseCode: 'AGR 101', courseName: 'Introduction to Agribusiness', instructor: 'Dr. Miriam Akinyi', date: '2025-08-22', time: '09:00-11:00', venue: 'Hall A', duration: '2 hours', type: 'CAT', status: 'postponed' },
]

export interface ExamResult {
  id: string
  studentId: string
  studentName: string
  courseCode: string
  courseName: string
  catScore: number
  finalScore: number
  totalScore: number
  grade: 'A' | 'B' | 'C' | 'D' | 'F'
  status: 'pass' | 'fail' | 'supplementary'
  semester: string
  year: number
}

export const EXAM_RESULTS: ExamResult[] = [
  { id: 'RES001', studentId: 'STU-2401', studentName: 'Brian Otieno', courseCode: 'CHD 201', courseName: 'Community Health Fundamentals', catScore: 45, finalScore: 55, totalScore: 100, grade: 'A', status: 'pass', semester: 'Semester 1', year: 2025 },
  { id: 'RES002', studentId: 'STU-2401', studentName: 'Brian Otieno', courseCode: 'CLM 301', courseName: 'Clinical Medicine Principles', catScore: 38, finalScore: 42, totalScore: 80, grade: 'B', status: 'pass', semester: 'Semester 1', year: 2025 },
  { id: 'RES003', studentId: 'STU-2402', studentName: 'Amina Hassan', courseCode: 'CLM 301', courseName: 'Clinical Medicine Principles', catScore: 42, finalScore: 48, totalScore: 90, grade: 'A', status: 'pass', semester: 'Semester 1', year: 2025 },
  { id: 'RES004', studentId: 'STU-2403', studentName: 'John Mwangi', courseCode: 'CHD 201', courseName: 'Community Health Fundamentals', catScore: 25, finalScore: 30, totalScore: 55, grade: 'C', status: 'pass', semester: 'Semester 1', year: 2025 },
  { id: 'RES005', studentId: 'STU-2404', studentName: 'Fatuma Salim', courseCode: 'F&B 201', courseName: 'Food and Beverage Operations', catScore: 20, finalScore: 28, totalScore: 48, grade: 'D', status: 'supplementary', semester: 'Semester 1', year: 2025 },
  { id: 'RES006', studentId: 'STU-2405', studentName: 'Kevin Njoroge', courseCode: 'ICT 201', courseName: 'Computer Applications', catScore: 40, finalScore: 50, totalScore: 90, grade: 'A', status: 'pass', semester: 'Semester 1', year: 2025 },
]

export interface TimetableEntry {
  id: string
  courseCode: string
  courseName: string
  instructor: string
  day: string
  startTime: string
  endTime: string
  venue: string
  department: string
}

export const TIMETABLE: TimetableEntry[] = [
  { id: 'TT001', courseCode: 'CHD 201', courseName: 'Community Health Fundamentals', instructor: 'Dr. Ruth Wambua', day: 'Monday', startTime: '08:00', endTime: '10:00', venue: 'Hall A', department: 'Department of Health and Social Sciences' },
  { id: 'TT002', courseCode: 'CHD 201', courseName: 'Community Health Fundamentals', instructor: 'Dr. Ruth Wambua', day: 'Wednesday', startTime: '08:00', endTime: '10:00', venue: 'Hall A', department: 'Department of Health and Social Sciences' },
  { id: 'TT003', courseCode: 'CLM 301', courseName: 'Clinical Medicine Principles', instructor: 'Prof. Ibrahim Juma', day: 'Tuesday', startTime: '08:00', endTime: '11:00', venue: 'Hall B', department: 'Department of Health and Social Sciences' },
  { id: 'TT004', courseCode: 'F&B 201', courseName: 'Food and Beverage Operations', instructor: 'Mr. Alex Owino', day: 'Monday', startTime: '10:00', endTime: '12:00', venue: 'Lab 1', department: 'Department of Catering and Hospitality' },
  { id: 'TT005', courseCode: 'AGR 101', courseName: 'Introduction to Agribusiness', instructor: 'Dr. Miriam Akinyi', day: 'Thursday', startTime: '14:00', endTime: '16:00', venue: 'Room 201', department: 'Department of Business and Accounting' },
  { id: 'TT006', courseCode: 'ICT 201', courseName: 'Computer Applications', instructor: 'Mr. George Muthomi', day: 'Monday', startTime: '10:00', endTime: '12:00', venue: 'Computer Lab', department: 'Department of Information Communication Technology' },
  { id: 'TT007', courseCode: 'DAT 301', courseName: 'Data Analytics Fundamentals', instructor: 'Ms. Cynthia Otieno', day: 'Wednesday', startTime: '14:00', endTime: '16:00', venue: 'Lab 2', department: 'Department of Data Science and Analytics' },
]

export interface StudentRecord {
  id: string
  studentId: string
  studentName: string
  program: string
  year: number
  semester: string
  gpa: number
  creditsCompleted: number
  totalCredits: number
  status: 'active' | 'probation' | 'suspended' | 'graduated'
  admissionDate: string
  expectedGraduation: string
}

export const STUDENT_RECORDS: StudentRecord[] = [
  { id: 'REC001', studentId: 'STU-2401', studentName: 'Brian Otieno', program: 'Diploma in Community Health and Development', year: 1, semester: 'Semester 1', gpa: 3.8, creditsCompleted: 12, totalCredits: 36, status: 'active', admissionDate: '2025-09-01', expectedGraduation: '2027-06-30' },
  { id: 'REC002', studentId: 'STU-2402', studentName: 'Amina Hassan', program: 'Diploma in Clinical Medicine', year: 1, semester: 'Semester 1', gpa: 3.5, creditsCompleted: 8, totalCredits: 36, status: 'active', admissionDate: '2025-09-01', expectedGraduation: '2028-06-30' },
  { id: 'REC003', studentId: 'STU-2403', studentName: 'John Mwangi', program: 'Certificate in Community Health and Development', year: 1, semester: 'Semester 1', gpa: 2.7, creditsCompleted: 6, totalCredits: 12, status: 'probation', admissionDate: '2025-09-01', expectedGraduation: '2026-06-30' },
  { id: 'REC004', studentId: 'STU-2404', studentName: 'Fatuma Salim', program: 'Diploma in Food and Beverage (Level 6)', year: 1, semester: 'Semester 1', gpa: 2.5, creditsCompleted: 8, totalCredits: 36, status: 'active', admissionDate: '2025-09-01', expectedGraduation: '2028-06-30' },
  { id: 'REC005', studentId: 'STU-2405', studentName: 'Kevin Njoroge', program: 'Diploma in Agribusiness', year: 1, semester: 'Semester 1', gpa: 3.9, creditsCompleted: 12, totalCredits: 36, status: 'active', admissionDate: '2025-09-01', expectedGraduation: '2028-06-30' },
  { id: 'REC006', studentId: 'STU-2406', studentName: 'Zuwena Kombo', program: 'Diploma in Data Science', year: 2, semester: 'Semester 2', gpa: 3.2, creditsCompleted: 24, totalCredits: 48, status: 'active', admissionDate: '2024-09-01', expectedGraduation: '2026-06-30' },
  { id: 'REC007', studentId: 'STU-2407', studentName: 'Moses Auma', program: 'Diploma in ICT', year: 1, semester: 'Semester 2', gpa: 2.1, creditsCompleted: 8, totalCredits: 36, status: 'suspended', admissionDate: '2024-09-01', expectedGraduation: '2027-06-30' },
  { id: 'REC008', studentId: 'STU-2408', studentName: 'Rehema Ally', program: 'Artisan in Computer Repair', year: 1, semester: 'Semester 1', gpa: 3.1, creditsCompleted: 6, totalCredits: 12, status: 'active', admissionDate: '2025-09-01', expectedGraduation: '2026-06-30' },
]

// ── PROCUREMENT DATA ──────────────────────────────────────────────────────────

export interface Requisition {
  id: string
  title: string
  department: string
  requestedBy: string
  items: string[]
  quantity: number
  justification: string
  status: 'draft' | 'pending' | 'approved' | 'rejected' | 'ordered' | 'delivered'
  budget: number
  createdAt: string
  approvedBy?: string
}

export const REQUISITIONS: Requisition[] = [
  { id: 'REQ-001', title: 'Office Laptops for IT Dept', department: 'Department of ICT', requestedBy: 'Mr. George Muthomi', items: ['HP ProBook 450'], quantity: 5, justification: 'Replacement of old staff laptops', status: 'approved', budget: 750000, createdAt: '2025-07-01', approvedBy: 'Mr. David Kamau' },
  { id: 'REQ-002', title: 'Lab Equipment - Nursing', department: 'Department of Health and Social Sciences', requestedBy: 'Dr. Ruth Wambua', items: ['Stethoscope', 'Blood Pressure Monitor', 'Pulse Oximeter'], quantity: 20, justification: 'New semester practicals', status: 'pending', budget: 340000, createdAt: '2025-07-03' },
  { id: 'REQ-003', title: 'Catering Supplies', department: 'Department of Catering and Hospitality', requestedBy: 'Mr. Alex Owino', items: ['Chef Knives', 'Cutting Boards', 'Uniforms'], quantity: 30, justification: 'Student practical kits', status: 'draft', budget: 120000, createdAt: '2025-07-05' },
  { id: 'REQ-004', title: 'Solar Installation Tools', department: 'Technical Department', requestedBy: 'Mr. Patrick Otieno', items: ['Solar Panel', 'Inverter', 'Battery'], quantity: 10, justification: 'Workshop upgrade', status: 'ordered', budget: 450000, createdAt: '2025-06-28' },
]

export interface Supplier {
  id: string
  name: string
  category: string
  email: string
  phone: string
  complianceExpiry: string
  rating: number
  totalPaid: number
  outstanding: number
  status: 'active' | 'inactive' | 'blacklisted'
}

export const SUPPLIERS: Supplier[] = [
  { id: 'SUP-001', name: 'TechHub Kenya', category: 'IT Equipment', email: 'sales@techhub.co.ke', phone: '+254 711 222 333', complianceExpiry: '2025-12-31', rating: 4.5, totalPaid: 1200000, outstanding: 0, status: 'active' },
  { id: 'SUP-002', name: 'MediCare Supplies', category: 'Medical Equipment', email: 'info@medicare.co.ke', phone: '+254 733 444 555', complianceExpiry: '2025-10-15', rating: 4.2, totalPaid: 890000, outstanding: 150000, status: 'active' },
  { id: 'SUP-003', name: 'Culinary Masters', category: 'Catering Supplies', email: 'orders@culinarymasters.co.ke', phone: '+254 722 666 777', complianceExpiry: '2025-09-30', rating: 3.8, totalPaid: 340000, outstanding: 0, status: 'active' },
  { id: 'SUP-004', name: 'BuildRight Hardware', category: 'Construction Materials', email: 'sales@buildright.co.ke', phone: '+254 744 888 999', complianceExpiry: '2026-01-20', rating: 4.0, totalPaid: 2100000, outstanding: 340000, status: 'active' },
]

export interface PurchaseOrder {
  id: string
  requisitionId: string
  supplierId: string
  items: string[]
  amount: number
  status: 'draft' | 'sent' | 'acknowledged' | 'delivered' | 'cancelled'
  orderDate: string
  expectedDelivery: string
}

export const PURCHASE_ORDERS: PurchaseOrder[] = [
  { id: 'PO-001', requisitionId: 'REQ-001', supplierId: 'SUP-001', items: ['HP ProBook 450 x5'], amount: 750000, status: 'delivered', orderDate: '2025-07-02', expectedDelivery: '2025-07-10' },
  { id: 'PO-002', requisitionId: 'REQ-004', supplierId: 'SUP-004', items: ['Solar Panel x10', 'Inverter x10', 'Battery x10'], amount: 450000, status: 'sent', orderDate: '2025-07-06', expectedDelivery: '2025-07-20' },
]

export interface ProcurementInvoice {
  id: string
  purchaseOrderId: string
  supplierId: string
  amount: number
  status: 'pending' | 'verified' | 'approved' | 'paid' | 'rejected'
  invoiceDate: string
  dueDate: string
  verifiedBy?: string
}

export const PROCUREMENT_INVOICES: ProcurementInvoice[] = [
  { id: 'PINV-001', purchaseOrderId: 'PO-001', supplierId: 'SUP-001', amount: 750000, status: 'paid', invoiceDate: '2025-07-10', dueDate: '2025-07-25', verifiedBy: 'Mr. David Kamau' },
  { id: 'PINV-002', purchaseOrderId: 'PO-002', supplierId: 'SUP-004', amount: 450000, status: 'pending', invoiceDate: '2025-07-15', dueDate: '2025-07-30' },
]

export interface Asset {
  id: string
  name: string
  category: string
  serialNumber: string
  purchaseOrderId: string
  cost: number
  department: string
  location: string
  status: 'active' | 'maintenance' | 'disposed'
  purchaseDate: string
  warrantyExpiry: string
}

export const ASSETS: Asset[] = [
  { id: 'AST-001', name: 'HP ProBook 450', category: 'IT Equipment', serialNumber: 'HPB-2025-001', purchaseOrderId: 'PO-001', cost: 150000, department: 'Department of ICT', location: 'Computer Lab', status: 'active', purchaseDate: '2025-07-10', warrantyExpiry: '2028-07-10' },
  { id: 'AST-002', name: 'Stethoscope', category: 'Medical Equipment', serialNumber: 'MED-2025-001', purchaseOrderId: 'PO-003', cost: 8500, department: 'Department of Health and Social Sciences', location: 'Nursing Lab', status: 'active', purchaseDate: '2025-07-12', warrantyExpiry: '2027-07-12' },
  { id: 'AST-003', name: 'Solar Panel 550W', category: 'Energy Equipment', serialNumber: 'SOL-2025-001', purchaseOrderId: 'PO-002', cost: 45000, department: 'Technical Department', location: 'Workshop', status: 'active', purchaseDate: '2025-07-18', warrantyExpiry: '2030-07-18' },
]

export interface InventoryItem {
  id: string
  name: string
  category: string
  quantity: number
  minStock: number
  unit: string
  department: string
  lastRestocked: string
  status: 'in_stock' | 'low_stock' | 'out_of_stock'
}

export const INVENTORY: InventoryItem[] = [
  { id: 'INV-001', name: 'A4 Paper', category: 'Office Supplies', quantity: 50, minStock: 20, unit: 'reams', department: 'Administration', lastRestocked: '2025-06-15', status: 'in_stock' },
  { id: 'INV-002', name: 'Printer Ink Cartridges', category: 'Office Supplies', quantity: 3, minStock: 10, unit: 'pieces', department: 'Administration', lastRestocked: '2025-06-20', status: 'low_stock' },
  { id: 'INV-003', name: 'Bandages', category: 'Medical Supplies', quantity: 0, minStock: 50, unit: 'boxes', department: 'Department of Health and Social Sciences', lastRestocked: '2025-05-10', status: 'out_of_stock' },
  { id: 'INV-004', name: 'Chef Knives', category: 'Catering Supplies', quantity: 15, minStock: 10, unit: 'pieces', department: 'Department of Catering and Hospitality', lastRestocked: '2025-06-25', status: 'in_stock' },
  { id: 'INV-005', name: 'Solar Batteries', category: 'Energy Equipment', quantity: 2, minStock: 5, unit: 'pieces', department: 'Technical Department', lastRestocked: '2025-07-01', status: 'low_stock' },
]

// ── SACCO DATA ────────────────────────────────────────────────────────────────

export interface SaccoMember {
  id: string
  name: string
  employeeId: string
  email: string
  phone: string
  joinDate: string
  membershipFee: number
  monthlyContribution: number
  totalSavings: number
  status: 'active' | 'inactive' | 'suspended'
}

export const SACCO_MEMBERS: SaccoMember[] = [
  { id: 'SAC-001', name: 'Mr. David Kamau', employeeId: 'EMP-008', email: 'd.kamau@tich.or.ke', phone: '+254 711 111 111', joinDate: '2023-01-15', membershipFee: 500, monthlyContribution: 2000, totalSavings: 85000, status: 'active' },
  { id: 'SAC-002', name: 'Ms. Winnie Adhiambo', employeeId: 'EMP-009', email: 'w.adhiambo@tich.or.ke', phone: '+254 722 222 222', joinDate: '2023-02-01', membershipFee: 500, monthlyContribution: 1500, totalSavings: 62000, status: 'active' },
  { id: 'SAC-003', name: 'Dr. Ruth Wambua', employeeId: 'EMP-012', email: 'r.wambua@tich.or.ke', phone: '+254 733 333 333', joinDate: '2023-03-10', membershipFee: 500, monthlyContribution: 3000, totalSavings: 145000, status: 'active' },
  { id: 'SAC-004', name: 'Mr. Alex Owino', employeeId: 'EMP-013', email: 'a.owino@tich.or.ke', phone: '+254 744 444 444', joinDate: '2023-04-05', membershipFee: 500, monthlyContribution: 1000, totalSavings: 38000, status: 'active' },
]

export interface SaccoLoan {
  id: string
  memberId: string
  memberName: string
  amount: number
  purpose: string
  status: 'pending' | 'approved' | 'disbursed' | 'repaid' | 'defaulted'
  applicationDate: string
  approvalDate?: string
  disbursementDate?: string
  repaymentDue: string
  amountPaid: number
  guarantor: string
}

export const SACCO_LOANS: SaccoLoan[] = [
  { id: 'SAC-LOAN-001', memberId: 'SAC-001', memberName: 'Mr. David Kamau', amount: 200000, purpose: 'Home renovation', status: 'repaid', applicationDate: '2024-01-15', approvalDate: '2024-01-20', disbursementDate: '2024-01-25', repaymentDue: '2024-07-25', amountPaid: 200000, guarantor: 'Dr. Lilian Gitau' },
  { id: 'SAC-LOAN-002', memberId: 'SAC-003', memberName: 'Dr. Ruth Wambua', amount: 300000, purpose: 'Vehicle purchase', status: 'disbursed', applicationDate: '2025-06-01', approvalDate: '2025-06-10', disbursementDate: '2025-06-15', repaymentDue: '2025-12-15', amountPaid: 50000, guarantor: 'Prof. James Kariuki' },
  { id: 'SAC-LOAN-003', memberId: 'SAC-002', memberName: 'Ms. Winnie Adhiambo', amount: 150000, purpose: 'School fees', status: 'pending', applicationDate: '2025-07-10', repaymentDue: '2025-10-10', amountPaid: 0, guarantor: 'Mr. David Kamau' },
]

export interface SaccoContribution {
  id: string
  memberId: string
  memberName: string
  amount: number
  type: 'monthly' | 'deposit' | 'withdrawal' | 'dividend'
  date: string
  description: string
}

export const SACCO_CONTRIBUTIONS: SaccoContribution[] = [
  { id: 'SC-001', memberId: 'SAC-001', memberName: 'Mr. David Kamau', amount: 2000, type: 'monthly', date: '2025-07-01', description: 'July 2025 contribution' },
  { id: 'SC-002', memberId: 'SAC-002', memberName: 'Ms. Winnie Adhiambo', amount: 1500, type: 'monthly', date: '2025-07-01', description: 'July 2025 contribution' },
  { id: 'SC-003', memberId: 'SAC-003', memberName: 'Dr. Ruth Wambua', amount: 3000, type: 'monthly', date: '2025-07-01', description: 'July 2025 contribution' },
  { id: 'SC-004', memberId: 'SAC-001', memberName: 'Mr. David Kamau', amount: 10000, type: 'deposit', date: '2025-06-15', description: 'Top-up deposit' },
]


