import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { ApiError } from '../lib/api'
import { Icon } from '../components/Icon'

export function LoginPage() {
  const { user, login } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('admin.ssdm@example.test')
  const [password, setPassword] = useState('Password123!')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    document.title = 'Masuk · Merit SDM POLRI'
  }, [])

  if (user) return <Navigate to="/dashboard" replace />

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      await login(email, password)
      navigate('/dashboard', { replace: true })
    } catch (exception) {
      setError(exception instanceof ApiError ? exception.message : 'Tidak dapat terhubung ke server.')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main className="login-page">
      <section className="login-story">
        <div className="story-content reveal">
          <span className="eyebrow dark">Sistem informasi sumber daya manusia</span>
          <h1>Sistem Merit<br/><em>Personel Polri</em></h1>
          <p>Pengelolaan data personel, kualifikasi, dan riwayat jabatan untuk mendukung pembinaan karier personel Polri.</p>
          <div className="story-metrics">
            <div><strong>01</strong><span>Profil terpadu</span></div>
            <div><strong>02</strong><span>Scope berjenjang</span></div>
            <div><strong>03</strong><span>Jejak karier</span></div>
          </div>
        </div>
        <div className="story-seal"><Icon name="shield" size={30}/><span>Data terotorisasi<br/>berdasarkan Satker</span></div>
      </section>

      <section className="login-panel">
        <div className="login-form-shell reveal delay-one">
          <form className="login-form" onSubmit={handleSubmit}>
            <div className="mobile-brand"><span className="brand-mark">MS</span><strong>Merit SDM</strong></div>
            <span className="eyebrow">Akses terbatas</span>
            <h2>Selamat datang kembali</h2>
            <p className="form-intro">Masuk menggunakan akun yang telah ditetapkan sesuai kewenangan organisasi.</p>

            {error && <div className="alert error" role="alert">{error}</div>}

            <label className="field-shell">
              <span>Email kedinasan</span>
              <input type="email" value={email} onChange={(event) => setEmail(event.target.value)} autoComplete="username" required />
            </label>
            <label className="field-shell">
              <span>Kata sandi</span>
              <input type="password" value={password} onChange={(event) => setPassword(event.target.value)} autoComplete="current-password" required />
            </label>

            <button className="primary-cta" type="submit" disabled={submitting}>
              <span>{submitting ? 'Memverifikasi…' : 'Masuk ke sistem'}</span>
              <span className="cta-icon"><Icon name="arrow" size={17}/></span>
            </button>
            <p className="security-note"><Icon name="shield" size={15}/> Sesi dilindungi token dan berakhir otomatis dalam 8 jam.</p>
          </form>
        </div>
      </section>
    </main>
  )
}
