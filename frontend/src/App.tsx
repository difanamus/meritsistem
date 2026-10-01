import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './auth/AuthContext'
import { AppShell } from './components/AppShell'
import { ProtectedRoute } from './components/ProtectedRoute'
import { LoginPage } from './pages/LoginPage'
import { PersonnelDetailPage } from './pages/PersonnelDetailPage'
import { PersonnelFormPage } from './pages/PersonnelFormPage'
import { PersonnelListPage } from './pages/PersonnelListPage'
import { QualificationFormPage } from './pages/QualificationFormPage'
import { MutationFormPage } from './pages/MutationFormPage'
import { ErrorPage } from './pages/ErrorPage'
import './App.css'

function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route element={<ProtectedRoute />}>
            <Route element={<AppShell />}>
              <Route path="/personel" element={<PersonnelListPage />} />
              <Route path="/personel/tambah" element={<PersonnelFormPage />} />
              <Route path="/personel/:id/edit" element={<PersonnelFormPage />} />
              <Route path="/personel/:id/kualifikasi/tambah" element={<QualificationFormPage />} />
              <Route path="/personel/:id/mutasi" element={<MutationFormPage />} />
              <Route path="/personel/:id" element={<PersonnelDetailPage />} />
              <Route path="/403" element={<ErrorPage code={403} title="Akses tidak diberikan" description="Data tersebut berada di luar kewenangan organisasi atau role akun Anda." />} />
            </Route>
          </Route>
          <Route path="/" element={<Navigate to="/personel" replace />} />
          <Route path="*" element={<ErrorPage code={404} title="Halaman tidak ditemukan" description="Alamat yang Anda buka tidak tersedia atau sudah dipindahkan." />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  )
}

export default App
