export const cacCheDo = ["sang", "toi", "heThong"];

// Tuần tự hóa ghi để chọn nhanh nhiều lần vẫn lưu lựa chọn cuối cùng.
export function taoTuyChonGiaoDien({ kho, thayDoi, baoLoi }) {
  let lan = 0;
  let hang = Promise.resolve();
  return {
    async khoiPhuc() {
      const moc = lan;
      try {
        const giaTri = await kho.doc();
        if (moc === lan && cacCheDo.includes(giaTri)) thayDoi(giaTri);
      } catch {
        baoLoi?.();
      }
    },
    chon(giaTri) {
      if (!cacCheDo.includes(giaTri)) return;
      lan++;
      thayDoi(giaTri);
      hang = hang.then(() => kho.luu(giaTri)).catch(() => baoLoi?.());
      return hang;
    },
  };
}
