import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './auth/AuthContext'
import { AppShell } from './components/AppShell'
import { ProtectedRoute, RoleProtectedRoute } from './components/ProtectedRoute'
import { LoginPage } from './pages/LoginPage'
import { PersonnelDetailPage } from './pages/PersonnelDetailPage'
import { PersonnelFormPage } from './pages/PersonnelFormPage'
import { PersonnelListPage } from './pages/PersonnelListPage'
import { QualificationFormPage } from './pages/QualificationFormPage'
import { PositionFormPage } from './pages/PositionFormPage'
import { MeritFormPage } from './pages/MeritFormPage'
import { MutationFormPage } from './pages/MutationFormPage'
import { ErrorPage } from './pages/ErrorPage'
import { UserFormPage } from './pages/UserFormPage'
import { UserListPage } from './pages/UserListPage'
import { ReferencePage } from './pages/ReferencePage'
import { DashboardPage } from './pages/DashboardPage'
import { SystemStatusPage } from './pages/SystemStatusPage'
import './App.css'

function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route element={<ProtectedRoute />}>
            <Route element={<AppShell />}>
              <Route path="/dashboard" element={<DashboardPage />} />
              <Route element={<RoleProtectedRoute roles={['system_admin']} />}>
                <Route path="/sistem" element={<SystemStatusPage />} />
              </Route>
              <Route path="/personel" element={<PersonnelListPage />} />
              <Route path="/referensi" element={<ReferencePage />} />
              <Route path="/personel/tambah" element={<PersonnelFormPage />} />
              <Route path="/personel/:id/edit" element={<PersonnelFormPage />} />
              <Route path="/personel/:id/kualifikasi/tambah" element={<QualificationFormPage />} />
              <Route path="/personel/:id/kualifikasi/:qualificationId/edit" element={<QualificationFormPage />} />
              <Route path="/personel/:id/riwayat-jabatan/tambah" element={<PositionFormPage />} />
              <Route path="/personel/:id/riwayat-jabatan/:positionId/edit" element={<PositionFormPage />} />
              <Route path="/personel/:id/merit/:kind/tambah" element={<MeritFormPage />} />
              <Route path="/personel/:id/merit/:kind/:recordId/edit" element={<MeritFormPage />} />
              <Route path="/personel/:id/mutasi" element={<MutationFormPage />} />
              <Route path="/personel/:id" element={<PersonnelDetailPage />} />
              <Route element={<RoleProtectedRoute roles={['system_admin', 'admin_ssdm']} />}>
                <Route path="/pengguna" element={<UserListPage />} />
                <Route path="/pengguna/tambah" element={<UserFormPage />} />
                <Route path="/pengguna/:id/edit" element={<UserFormPage />} />
              </Route>
              <Route path="/403" element={<ErrorPage code={403} title="Akses tidak diberikan" description="Data tersebut berada di luar kewenangan organisasi atau role akun Anda." />} />
            </Route>
          </Route>
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
          <Route path="*" element={<ErrorPage code={404} title="Halaman tidak ditemukan" description="Alamat yang Anda buka tidak tersedia atau sudah dipindahkan." />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  )
}

export default App
