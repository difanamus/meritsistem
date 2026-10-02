import { HistoryPagination } from './PersonnelHistory'
import { useHistory } from '../lib/useHistory'

type DisciplineSnapshot = {
  id: number
  source_record_id: string
  jenis: 'disiplin' | 'kode_etik'
  ringkasan: string
  nomor_keputusan: string
  tanggal_keputusan: string
  sanksi: string
  instansi_penerbit: string
  status_keputusan: 'final' | 'dibatalkan'
  keterangan_pembatalan: string | null
}

export function DisciplinePrototypeSection({ personnelId }: { personnelId: number }) {
  const history = useHistory<DisciplineSnapshot>(personnelId, 'disiplin-prototype')
  return <section className="profile-section reveal" aria-label="Riwayat Disiplin dan Kode Etik">
    <div className="section-heading"><div><span className="eyebrow">Prototype integrasi · hanya baca</span><h2>Riwayat Disiplin &amp; Kode Etik</h2></div></div>
    <div className="alert" role="note"><strong>DEMO — belum terhubung ke sistem Propam.</strong><p>Seluruh catatan di sini sintetis, bukan keputusan atau perkara nyata. Belum pernah disinkronkan. Tidak digunakan untuk skor merit atau keputusan karier otomatis.</p><p>Pada pengembangan berikutnya, keputusan final dan pembatalannya dapat dibaca dari sistem sumber melalui REST API resmi. Integrasi dan delta sync belum diimplementasikan.</p></div>
    {history.error && <div className="alert error" role="alert">{history.error} <button type="button" className="text-action" onClick={history.reload}>Coba lagi</button></div>}
    {history.loading ? <div className="empty-inline" role="status">Memuat riwayat prototype…</div> : !history.error && <div className="qualification-grid">
      {history.items.map((item) => <article className="qualification-card history-card" key={item.id}>
        <span>DATA SINTETIS DEMO · {item.jenis === 'kode_etik' ? 'Kode etik' : 'Disiplin'}</span><h3>{item.nomor_keputusan}</h3>
        <p><strong>{item.status_keputusan === 'dibatalkan' ? 'Dibatalkan — bukan sanksi aktif' : 'Keputusan final (simulasi)'}</strong></p><p>{item.ringkasan}</p>
        <dl className="history-facts"><div><dt>Tanggal keputusan</dt><dd>{item.tanggal_keputusan}</dd></div><div><dt>Instansi penerbit</dt><dd>{item.instansi_penerbit}</dd></div><div><dt>{item.status_keputusan === 'dibatalkan' ? 'Sanksi dalam keputusan terdahulu' : 'Sanksi dalam keputusan'}</dt><dd>{item.sanksi}</dd></div><div><dt>ID sumber demo</dt><dd>{item.source_record_id}</dd></div></dl>
        {item.keterangan_pembatalan && <p className="history-note">{item.keterangan_pembatalan}</p>}
      </article>)}
      {history.items.length === 0 && <div className="empty-inline">Belum ada catatan dalam sistem. Ini bukan bukti bahwa personel tidak pernah melakukan pelanggaran.</div>}
    </div>}
    <HistoryPagination meta={history.meta} loading={history.loading} onPage={history.changePage} />
  </section>
}
