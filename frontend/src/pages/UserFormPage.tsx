import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { ApiError, apiRequest } from '../lib/api'
import { UnitSearchSelect } from '../components/UnitSearchSelect'
import type { ReferenceItem, User, UserRole } from '../types'

interface ScopeDraft {
  unit_organisasi_id: string
  unit_organisasi?: ReferenceItem | null
  scope_type: 'own_unit' | 'unit_and_descendants'
  is_active: boolean
  berlaku_mulai: string
  berlaku_sampai: string
}

interface UserForm {
  name: string
  email: string
  password: string
  password_confirmation: string
  role: UserRole
  is_active: boolean
  scopes: ScopeDraft[]
}

const emptyScope = (): ScopeDraft => ({ unit_organisasi_id: '', scope_type: 'own_unit', is_active: true, berlaku_mulai: '', berlaku_sampai: '' })
const initialForm: UserForm = { name: '', email: '', password: '', password_confirmation: '', role: 'operator', is_active: true, scopes: [emptyScope()] }

export function UserFormPage() {
  const { id } = useParams()
  const isEdit = Boolean(id)
  const navigate = useNavigate()
  const { user: actor } = useAuth()
  const [form, setForm] = useState<UserForm>(initialForm)
  const [loading, setLoading] = useState(isEdit)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')
  const [errors, setErrors] = useState<Record<string, string[]>>({})

  useEffect(() => {
    document.title = `${isEdit ? 'Edit' : 'Tambah'} Pengguna · Merit SDM POLRI`
    if (!id) return
    void apiRequest<{ data: User }>(`/users/${id}`)
      .then((response) => setForm({
        name: response.data.name,
        email: response.data.email,
        password: '',
        password_confirmation: '',
        role: response.data.role,
        is_active: response.data.is_active,
        scopes: response.data.scopes.length ? response.data.scopes.map((scope) => ({
          unit_organisasi_id: String(scope.unit_organisasi.id),
          unit_organisasi: scope.unit_organisasi,
          scope_type: scope.scope_type,
          is_active: scope.is_active,
          berlaku_mulai: scope.berlaku_mulai ?? '',
          berlaku_sampai: scope.berlaku_sampai ?? '',
        })) : [emptyScope()],
      }))
      .catch((exception) => setError(exception instanceof ApiError ? exception.message : 'Data pengguna tidak dapat dimuat.'))
      .finally(() => setLoading(false))
  }, [id, isEdit])

  const fieldError = (field: string) => errors[field]?.[0]
  const setField = <K extends keyof UserForm>(field: K, value: UserForm[K]) => setForm((current) => ({ ...current, [field]: value }))
  const updateScope = <K extends keyof ScopeDraft>(index: number, field: K, value: ScopeDraft[K]) => setForm((current) => ({ ...current, scopes: current.scopes.map((scope, scopeIndex) => scopeIndex === index ? { ...scope, [field]: value } : scope) }))
  const removeScope = (index: number) => setForm((current) => ({ ...current, scopes: current.scopes.filter((_, scopeIndex) => scopeIndex !== index) }))

  const handleRoleChange = (role: UserRole) => setForm((current) => ({ ...current, role, scopes: role === 'operator' ? (current.scopes.length ? current.scopes : [emptyScope()]) : [] }))

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault()
    setSubmitting(true)
    setError('')
    setErrors({})
    const payload = {
      ...form,
      scopes: form.role === 'operator' ? form.scopes.map((scope) => ({ unit_organisasi_id: Number(scope.unit_organisasi_id), scope_type: scope.scope_type, is_active: scope.is_active, berlaku_mulai: scope.berlaku_mulai || null, berlaku_sampai: scope.berlaku_sampai || null })) : [],
    }
    try {
      await apiRequest(isEdit ? `/users/${id}` : '/users', { method: isEdit ? 'PUT' : 'POST', body: JSON.stringify(payload) })
      navigate('/pengguna')
    } catch (exception) {
      if (exception instanceof ApiError) {
        setError(exception.message)
        setErrors(exception.errors)
      } else setError('Pengguna tidak dapat disimpan.')
    } finally {
      setSubmitting(false)
    }
  }

  if (loading) return <div className="page-loader"><span/><p>Memuat formulir pengguna…</p></div>

  return <div className="page-wrap form-page">
    <div className="detail-back reveal"><Link to="/pengguna">← Batal dan kembali</Link></div>
    <header className="page-header reveal"><div><span className="eyebrow">Administrasi akses</span><h1>{isEdit ? 'Edit pengguna' : 'Tambah pengguna'}</h1><p>Role menentukan kewenangan; scope membatasi unit yang dapat dikelola Operator.</p></div></header>
    <form onSubmit={handleSubmit} className="person-form">
      {error && <div className="alert error full-span">{error}</div>}
      <section className="form-section-shell reveal delay-one"><div className="form-section-core"><div className="form-section-heading"><span>01</span><div><h2>Identitas akun</h2><p>Informasi untuk mengenali dan mengautentikasi pengguna.</p></div></div><div className="form-grid">
        <label className="wide"><span>Nama pengguna</span><input value={form.name} onChange={(event) => setField('name', event.target.value)} required/>{fieldError('name') && <small>{fieldError('name')}</small>}</label>
        <label className="wide"><span>Email login</span><input type="email" value={form.email} onChange={(event) => setField('email', event.target.value)} required/>{fieldError('email') && <small>{fieldError('email')}</small>}</label>
        <label><span>{isEdit ? 'Password baru (opsional)' : 'Password'}</span><input type="password" value={form.password} onChange={(event) => setField('password', event.target.value)} required={!isEdit} autoComplete="new-password"/>{fieldError('password') && <small>{fieldError('password')}</small>}</label>
        <label><span>Konfirmasi password</span><input type="password" value={form.password_confirmation} onChange={(event) => setField('password_confirmation', event.target.value)} required={!isEdit} autoComplete="new-password"/></label>
      </div></div></section>

      <section className="form-section-shell reveal delay-two"><div className="form-section-core"><div className="form-section-heading"><span>02</span><div><h2>Role & status</h2><p>Admin SSDM hanya dapat membuat dan mengelola akun Operator.</p></div></div><div className="form-grid">
        <label><span>Role</span><select value={form.role} disabled={actor?.role === 'admin_ssdm'} onChange={(event) => handleRoleChange(event.target.value as UserRole)}>{actor?.role === 'system_admin' && <><option value="system_admin">System Admin</option><option value="admin_ssdm">Admin SSDM</option></>}<option value="operator">Operator</option></select>{fieldError('role') && <small>{fieldError('role')}</small>}</label>
        <label><span>Status akun</span><select value={form.is_active ? 'active' : 'inactive'} onChange={(event) => setField('is_active', event.target.value === 'active')}><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select>{fieldError('is_active') && <small>{fieldError('is_active')}</small>}</label>
      </div></div></section>

      {form.role === 'operator' && <section className="form-section-shell reveal delay-three"><div className="form-section-core"><div className="form-section-heading scope-heading"><span>03</span><div><h2>Cakupan organisasi</h2><p>Operator wajib memiliki minimal satu scope aktif. Satu akun dapat menangani beberapa unit.</p></div><button type="button" className="scope-add" onClick={() => setForm((current) => ({ ...current, scopes: [...current.scopes, emptyScope()] }))}>+ Tambah scope</button></div>
        {fieldError('scopes') && <div className="inline-field-error">{fieldError('scopes')}</div>}
        <div className="scope-editor">{form.scopes.map((scope, index) => <div className="scope-editor-row" key={index}>
          <div className="scope-row-title"><strong>Scope {String(index + 1).padStart(2, '0')}</strong>{form.scopes.length > 1 && <button type="button" onClick={() => removeScope(index)}>Hapus</button>}</div>
          <div className="form-grid">
            <UnitSearchSelect label="Unit organisasi / Satker" value={scope.unit_organisasi_id} current={scope.unit_organisasi} onChange={(value, unit) => setForm((current) => ({ ...current, scopes: current.scopes.map((item, scopeIndex) => scopeIndex === index ? { ...item, unit_organisasi_id: value, unit_organisasi: unit ?? null } : item) }))} error={fieldError(`scopes.${index}.unit_organisasi_id`)} />
            <label><span>Jenis cakupan</span><select value={scope.scope_type} onChange={(event) => updateScope(index, 'scope_type', event.target.value as ScopeDraft['scope_type'])}><option value="own_unit">Unit sendiri</option><option value="unit_and_descendants">Unit dan seluruh bawahannya</option></select></label>
            <label><span>Status scope</span><select value={scope.is_active ? 'active' : 'inactive'} onChange={(event) => updateScope(index, 'is_active', event.target.value === 'active')}><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></label>
            <label><span>Berlaku mulai (opsional)</span><input type="date" value={scope.berlaku_mulai} onChange={(event) => updateScope(index, 'berlaku_mulai', event.target.value)}/></label>
            <label><span>Berlaku sampai (opsional)</span><input type="date" value={scope.berlaku_sampai} onChange={(event) => updateScope(index, 'berlaku_sampai', event.target.value)}/>{fieldError(`scopes.${index}.berlaku_sampai`) && <small>{fieldError(`scopes.${index}.berlaku_sampai`)}</small>}</label>
          </div>
        </div>)}</div>
      </div></section>}

      <div className="form-actions"><Link to="/pengguna" className="secondary-cta">Batal</Link><button className="primary-cta" disabled={submitting}><span>{submitting ? 'Menyimpan…' : isEdit ? 'Simpan perubahan' : 'Buat pengguna'}</span><span className="cta-icon">✓</span></button></div>
    </form>
  </div>
}
