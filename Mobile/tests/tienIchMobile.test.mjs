import test from "node:test";
import assert from "node:assert/strict";
import { taoTuyChonGiaoDien } from "../src/services/tuyChonGiaoDien.js";
import { docLienKet, docPush } from "../src/utils/lienKet.js";
import { taoKenhChat } from "../src/services/kenhChat.js";

test("Giao diện: đọc chậm không ghi đè lựa chọn mới, ghi nhanh giữ đúng thứ tự và phục hồi lần mở sau", async () => {
  let traDoc, xongGhi, luu, hien;
  const writes = [];
  const kho = {
    doc: () => new Promise((r) => (traDoc = r)),
    luu: async (v) => {
      writes.push(v);
      if (v === "toi") await new Promise((r) => (xongGhi = r));
      luu = v;
    },
  };
  const q = taoTuyChonGiaoDien({ kho, thayDoi: (v) => (hien = v) });
  const doc = q.khoiPhuc();
  q.chon("toi");
  const cho = q.chon("heThong");
  q.chon("khong-hop-le");
  traDoc("sang");
  await doc;
  assert.equal(hien, "heThong");
  xongGhi();
  await cho;
  assert.deepEqual(writes, ["toi", "heThong"]);
  assert.equal(luu, "heThong");
  await taoTuyChonGiaoDien({
    kho: { doc: async () => luu },
    thayDoi: (v) => (hien = v),
  }).khoiPhuc();
  assert.equal(hien, "heThong");
});

test("Giao diện: lỗi lưu không chặn chọn/lưu lại; giá trị trên máy không hợp lệ bị bỏ qua", async () => {
  let hien = "sang",
    loi = 0,
    thu = 0;
  const q = taoTuyChonGiaoDien({
    kho: {
      doc: async () => "bad",
      luu: async () => {
        if (++thu === 1) throw Error();
      },
    },
    thayDoi: (v) => (hien = v),
    baoLoi: () => loi++,
  });
  await q.khoiPhuc();
  assert.equal(hien, "sang");
  await q.chon("toi");
  await q.chon("heThong");
  assert.equal(loi, 1);
  assert.equal(hien, "heThong");
});

test("Link reset bản cài/Expo Go: email và mã hợp lệ; không lấy quyền hoặc trạng thái thanh toán từ URL", () => {
  const token = "a".repeat(64);
  for (const base of [
    "fitforge://dat-lai-mat-khau",
    "exp://192.168.1.15:8086/--/dat-lai-mat-khau",
  ]) {
    assert.deepEqual(
      docLienKet(`${base}#token=${token}&email=QA%40example.test`),
      {
        name: "KhoiPhuc",
        params: { token, email: "qa@example.test" },
        congKhai: true,
      },
    );
  }
  const don = docLienKet("fitforge://don-hang/42?status=PAID&amount=1");
  assert.equal(don.name, "ChiTietDon");
  assert.equal(don.params.id, 42);
  assert.equal(don.params.status, undefined);
  assert.equal(don.chiKhach, true);
});

test("Link và push: từ chối scheme, đường dẫn, mã, ID lạ và thông báo của tài khoản khác", () => {
  const token = "a".repeat(64);
  for (const u of [
    "https://evil.test/don-hang/42",
    "file:///don-hang/42",
    "fitforge://user@don-hang/42",
    "fitforge://don-hang/9007199254740992",
    "fitforge://don-hang/%34%32",
    "fitforge://don-hang/0",
    "fitforge://admin/tai-khoan",
    "fitforge://dat-lai-mat-khau#token=bad&email=a@b.test",
    `fitforge://dat-lai-mat-khau#token=${token}&token=${token}&email=a@b.test`,
  ])
    assert.equal(docLienKet(u), null);
  const kh = { id: 1, vai_tro: "KHACH_HANG" };
  assert.equal(
    docPush({ tai_khoan_id: 2, duong_dan: "/hoi-thoai/4" }, kh),
    null,
  );
  assert.deepEqual(
    docPush({ tai_khoan_id: 1, duong_dan: "/hoi-thoai/4" }, kh),
    { name: "HoiThoai", params: { id: 4 } },
  );
  assert.equal(
    docPush({ tai_khoan_id: 1, duong_dan: "/pt/lich-hen/4" }, kh),
    null,
  );
});

test("Realtime lịch chỉ nhận đúng kênh, tải lại sau subscribe và dừng listener khi đóng", async () => {
  let socket,
    so = 0;
  const q = taoKenhChat({
    url: "ws://localhost:8080",
    appKey: "qa",
    taiKhoanId: 12,
    xacThuc: async () => ({ auth: "qa" }),
    dongBo: () => {},
    dongBoLich: () => so++,
    taoSocket: () => (socket = { readyState: 1, send() {}, close() {} }),
  });
  q.ketNoi();
  const nhan = (event, channel, data = { can_dong_bo: true }) =>
    socket.onmessage({ data: JSON.stringify({ event, channel, data }) });
  await nhan(
    "pusher_internal:subscription_succeeded",
    "private-chat.tai-khoan.12",
  );
  await nhan("lich.cap-nhat", "private-chat.tai-khoan.12");
  await nhan("lich.cap-nhat", "private-chat.tai-khoan.13");
  await nhan("lich.cap-nhat", "private-chat.tai-khoan.12", {
    can_dong_bo: false,
  });
  assert.equal(so, 2);
  q.dong();
  assert.equal(socket.onmessage, null);
});
