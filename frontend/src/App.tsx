import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './auth/AuthContext'
import { AppShell } from './components/AppShell'
import { ProtectedRoute } from './components/ProtectedRoute'
import { LoginPage } from './pages/LoginPage'
import { PersonnelDetailPage } from './pages/PersonnelDetailPage'
import { PersonnelFormPage } from './pages/PersonnelFormPage'
import { PersonnelListPage } from './pages/PersonnelListPage'
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
              <Route path="/personel/:id" element={<PersonnelDetailPage />} />
            </Route>
          </Route>
          <Route path="*" element={<Navigate to="/personel" replace />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  )
}

export default App
