// Reverb dùng giao thức Pusher 7. Socket chỉ báo đồng bộ; nội dung và quyền luôn đọc qua HTTP.
export function taoKenhChat({
  url,
  appKey,
  taiKhoanId,
  xacThuc,
  dongBo,
  dongBoLich,
  trangThai,
  taoSocket = (u) => new WebSocket(u),
  hen = setTimeout,
  huyHen = clearTimeout,
}) {
  let socket = null;
  let dung = true;
  let theHe = 0;
  let thu = 0;
  let timer = null;
  let nhip = null;
  let choPong = null;
  let auth = null;
  let activity = 120000;
  const kenh = `private-chat.tai-khoan.${taiKhoanId}`;
  const gui = (event, data) => {
    if (socket?.readyState === 1) socket.send(JSON.stringify({ event, data }));
  };
  function don() {
    huyHen(timer);
    huyHen(nhip);
    huyHen(choPong);
    timer = nhip = choPong = null;
    auth?.abort();
    auth = null;
    if (socket) {
      const cu = socket;
      socket = null;
      cu.onopen = cu.onmessage = cu.onerror = cu.onclose = null;
      cu.close();
    }
  }
  function datNhip() {
    huyHen(nhip);
    nhip = hen(() => {
      gui("pusher:ping", {});
      choPong = hen(() => thuLai(), 30000);
    }, activity);
  }
  function thuLai(code = 0) {
    theHe++;
    don();
    trangThai?.("cho");
    if (dung || (code >= 4000 && code < 4100)) return;
    timer = hen(mo, Math.min(30000, 1000 * 2 ** Math.min(thu++, 5)));
  }
  function mo() {
    if (dung || !url || !appKey) return;
    don();
    const lan = ++theHe;
    trangThai?.("dang-noi");
    try {
      socket = taoSocket(
        `${url.replace(/\/$/, "")}/app/${encodeURIComponent(appKey)}?protocol=7&client=mobile&version=1`,
      );
    } catch {
      thuLai();
      return;
    }
    timer = hen(() => thuLai(), 15000);
    socket.onmessage = async ({ data: frame }) => {
      if (dung || lan !== theHe) return;
      try {
        const f = JSON.parse(frame);
        const data = typeof f.data === "string" ? JSON.parse(f.data) : f.data;
        if (f.event === "pusher:connection_established") {
          activity = Math.min(
            120000,
            Math.max(1000, Number(data.activity_timeout || 120) * 1000),
          );
          auth = new AbortController();
          const ketQua = await xacThuc(data.socket_id, kenh, auth.signal);
          if (dung || lan !== theHe) return;
          if (!ketQua?.auth) {
            thuLai(4001);
            return;
          }
          gui("pusher:subscribe", { channel: kenh, auth: ketQua.auth });
          datNhip();
        } else if (
          f.event === "pusher_internal:subscription_succeeded" &&
          f.channel === kenh
        ) {
          huyHen(timer);
          timer = null;
          thu = 0;
          trangThai?.("da-noi");
          dongBo();
          dongBoLich?.();
        } else if (f.event === "pusher:ping") {
          gui("pusher:pong", {});
          datNhip();
        } else if (f.event === "pusher:pong") {
          huyHen(choPong);
          choPong = null;
          datNhip();
        } else if (f.event === "pusher:error") {
          thuLai(Number(data?.code || 0));
        } else {
          datNhip();
          if (
            f.channel === kenh &&
            f.event === "lich.cap-nhat" &&
            data?.can_dong_bo === true
          )
            dongBoLich?.();
          if (
            f.channel === kenh &&
            f.event === "chat.cap-nhat" &&
            data?.can_dong_bo === true
          )
            dongBo();
        }
      } catch (loi) {
        if (lan === theHe && !dung)
          thuLai([401, 403].includes(loi.status) ? 4001 : 0);
      }
    };
    socket.onerror = () => {
      if (lan === theHe) thuLai();
    };
    socket.onclose = (e) => {
      if (lan === theHe) thuLai(e.code);
    };
  }
  return {
    ketNoi() {
      if (!dung) return;
      dung = false;
      mo();
    },
    dong() {
      dung = true;
      theHe++;
      don();
      trangThai?.("cho");
    },
  };
}
