const laPt = document.title.includes('Huấn luyện viên')
const laAdmin = document.title.includes('Admin')
document.body.dataset.previewRole = laPt ? 'pt' : laAdmin ? 'admin' : 'kh'
for (const phanTu of document.querySelectorAll('span,div,p')) {
  if (phanTu.children.length) continue
  const noiDung = phanTu.textContent.trim()
  if (noiDung === 'Hội viên tích cực' || noiDung === 'Gợi ý bởi Tr0ond AI') phanTu.remove()
  if (noiDung.includes('Phản hồi thường trong')) phanTu.textContent = 'Huấn luyện viên phụ trách'
}
if (laAdmin) {
  const chanTrang = document.querySelector('footer')
  if (chanTrang) chanTrang.textContent = 'Hệ thống huấn luyện cá nhân'
  for (const phanTu of document.querySelectorAll('div,span,p')) {
    if (phanTu.children.length) continue
    const noiDung = phanTu.textContent.trim()
    if (noiDung === '56 buổi PT hoàn thành') phanTu.textContent = '45 buổi PT hoàn thành'
    if (noiDung.includes('Ca tập đã') && noiDung.includes('xác nhận')) phanTu.textContent = 'Buổi tập đã được PT xác nhận hoàn thành'
    if (noiDung.startsWith('Dữ liệu vận hành thực tế')) phanTu.textContent = 'Tổng quan hoạt động huấn luyện'
  }
  const tieuDe = [...document.querySelectorAll('h2')].find(phanTu => phanTu.textContent.toLowerCase().includes('dòng tiền hệ thống'))
  if (tieuDe) {
    const bieuDo = tieuDe.parentElement.parentElement.nextElementSibling
    const khung = document.createElement('figure')
    khung.className = 'fm-money-chart'
    khung.setAttribute('aria-label','Tiền nhận theo tuần: 10, 12, 11, 15 triệu đồng; hoàn 2 triệu ở tuần 2. Tổng nhận 48 triệu, hoàn 2 triệu.')
    const truc = document.createElement('div')
    truc.className = 'fm-chart-axis'
    for (const nhan of ['15 tr','10 tr','5 tr','0']) {
      const muc = document.createElement('span')
      muc.textContent = nhan
      truc.append(muc)
    }
    khung.append(truc)
    for (const [i,tienNhan] of [10,12,11,15].entries()) {
      const cot = document.createElement('div')
      cot.className = 'fm-chart-column'
      const thanh = document.createElement('div')
      thanh.className = 'fm-chart-bars'
      for (const [loai,giaTri] of [['nhan',tienNhan],['hoan',i===1 ? 2 : 0]]) {
        const bar = document.createElement('div')
        bar.className = 'fm-bar fm-bar-'+loai
        bar.style.height = (giaTri/15*100)+'%'
        bar.title = (loai === 'nhan' ? 'Đã nhận: ' : 'Đã hoàn: ')+giaTri+' triệu đồng'
        if (giaTri) { const so = document.createElement('span'); so.textContent = giaTri+' tr'; bar.append(so) }
        thanh.append(bar)
      }
      const nhan = document.createElement('span')
      nhan.textContent = 'Tuần '+(i+1)
      cot.append(thanh,nhan)
      khung.append(cot)
    }
    bieuDo.replaceChildren(khung)
  }
}
if (laPt) {
  for (const nut of document.querySelectorAll('button')) {
    if (nut.textContent.includes('Điểm danh')) nut.remove()
  }
  for (const phanTu of document.querySelectorAll('span')) {
    if (phanTu.textContent.includes('Central Hub')) phanTu.remove()
    if (phanTu.textContent.trim() === '2 đã xong, 2 sắp diễn ra') phanTu.textContent = '1 đã hoàn thành · 1 chờ xác nhận · 2 sắp tới'
    if (phanTu.textContent.trim() === 'Phản hồi trước ca 12h') phanTu.textContent = '2 yêu cầu chờ xác nhận'
  }
  const tieuDe = [...document.querySelectorAll('h2')].find(phanTu => phanTu.textContent.includes('Yêu cầu đặt lịch chờ duyệt'))
  if (tieuDe) {
    tieuDe.textContent = 'Yêu cầu đặt lịch chờ duyệt'
    const khuVuc = tieuDe.parentElement.parentElement.parentElement
    for (const hang of khuVuc.querySelectorAll('.divide-y > div')) {
      const thaoTac = hang.lastElementChild
      thaoTac.replaceChildren()
      const trangThai = document.createElement('span')
      trangThai.className = 'text-xs text-on-surface-variant'
      trangThai.textContent = 'Chờ xác nhận'
      thaoTac.append(trangThai)
      for (const [nhanNut,noiBat] of [['Từ chối',false],['Xác nhận',true]]) {
        const nut = document.createElement('button')
        nut.type = 'button'
        nut.className = 'min-h-[44px] px-3 text-xs font-medium rounded ' + (noiBat ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface')
        nut.textContent = nhanNut
        thaoTac.append(nut)
      }
    }
  }
}
