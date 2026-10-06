import { dichThongBao } from "./traoDoi.js";

export function docLienKet(url) {
  if (typeof url !== "string" || url.length > 4096) return null;
  try {
    const u = new URL(url);
    let duong;
    if (u.protocol === "fitforge:" && !u.username && !u.password && !u.port) {
      duong = `${u.hostname}${u.pathname}`.replace(/\/$/, "");
    } else if (
      ["exp:", "exps:"].includes(u.protocol) &&
      u.pathname.startsWith("/--/")
    ) {
      duong = u.pathname.slice(4).replace(/\/$/, "");
    } else return null;
    if (duong === "dat-lai-mat-khau") {
      // Fragment giữ mã khôi phục khỏi access log của trang web trung gian.
      const q = new URLSearchParams(u.hash.slice(1) || u.search.slice(1));
      if (q.getAll("token").length !== 1 || q.getAll("email").length !== 1)
        return null;
      const token = q.get("token");
      const email = q.get("email")?.trim().toLowerCase();
      if (
        !/^[a-f0-9]{64}$/.test(token || "") ||
        email.length > 191 ||
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
      )
        return null;
      return { name: "KhoiPhuc", params: { token, email }, congKhai: true };
    }
    const m = duong.match(/^(don-hang|lich-hen)\/([1-9][0-9]*)$/);
    if (!m || !Number.isSafeInteger(Number(m[2]))) return null;
    return {
      name: m[1] === "don-hang" ? "ChiTietDon" : "ChiTietLich",
      params: { id: Number(m[2]), quayVe: Date.now() },
      chiKhach: m[1] === "don-hang",
    };
  } catch {
    return null;
  }
}

export function docPush(data, taiKhoan) {
  // Thông báo của tài khoản cũ trên cùng máy không mở dữ liệu của phiên mới.
  if (!taiKhoan || Number(data?.tai_khoan_id) !== taiKhoan.id) return null;
  const m =
    typeof data.duong_dan === "string" &&
    data.duong_dan.match(/^\/hoi-thoai\/([1-9][0-9]*)$/);
  if (m && Number.isSafeInteger(Number(m[1])))
    return { name: "HoiThoai", params: { id: Number(m[1]) } };
  return dichThongBao(data.duong_dan, taiKhoan.vai_tro);
}
