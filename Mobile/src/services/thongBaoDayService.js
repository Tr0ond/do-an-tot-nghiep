import { goiApi } from "../utils/http";

export const thongBaoDayService = {
  tai: (token) => goiApi("/mobile/thong-bao-day", { token }),
  luu: (token, expo_token) =>
    goiApi("/mobile/thong-bao-day", {
      token,
      method: "PUT",
      duLieu: { expo_token },
    }),
  tat: (token) => goiApi("/mobile/thong-bao-day", { token, method: "DELETE" }),
};
