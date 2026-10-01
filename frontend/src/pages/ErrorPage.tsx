import { useEffect } from 'react'
import { Link } from 'react-router-dom'

interface ErrorPageProps {
  code: 403 | 404
  title: string
  description: string
}

export function ErrorPage({ code, title, description }: ErrorPageProps) {
  useEffect(() => {
    document.title = `${code} · Merit SDM POLRI`
  }, [code])

  return (
    <main className="error-page">
      <section className="error-shell reveal">
        <div className="error-core">
          <span className="eyebrow">Status akses · {code}</span>
          <strong>{code}</strong>
          <h1>{title}</h1>
          <p>{description}</p>
          <Link className="primary-cta" to="/personel">
            <span>Kembali ke data personel</span>
            <span className="cta-icon">→</span>
          </Link>
        </div>
      </section>
    </main>
  )
}
