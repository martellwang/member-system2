<script>
(() => {
  const APP_BASE = document.querySelector('meta[name="app-base"]')?.content || '';
  const bars = [
    {
      bar: document.querySelector('.member-timeout-bar'),
      countdown: document.getElementById('member-timeout-countdown'),
      secondsAttr: 'memberTimeout',
      redirectAttr: 'logoutUrl',
      logoutAttr: 'logoutUrl',
      logoutMethod: 'GET',
      fallbackUrl: '/member/logout',
    },
    {
      bar: document.querySelector('.admin-timeout-bar'),
      countdown: document.getElementById('admin-timeout-countdown'),
      secondsAttr: 'timeoutSeconds',
      redirectAttr: 'timeoutRedirect',
      logoutAttr: 'timeoutLogout',
      logoutMethod: 'POST',
      fallbackUrl: '/admin/login',
    },
  ];

  function format(seconds) {
    const safeSeconds = Math.max(0, seconds);
    const hours = Math.floor(safeSeconds / 3600);
    const minutes = Math.floor((safeSeconds % 3600) / 60);
    const secs = safeSeconds % 60;
    if (hours > 0) {
      return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
  }

  async function expireSession(bar, logoutUrl, logoutMethod, redirectUrl) {
    if (bar.timeoutState?.expired) return;
    bar.timeoutState.expired = true;

    try {
      if (logoutMethod === 'POST') {
        await fetch(logoutUrl, {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
          keepalive: true,
        });
      } else {
        await fetch(logoutUrl, {
          method: 'GET',
          headers: { 'Accept': 'text/html,application/xhtml+xml,application/json' },
          keepalive: true,
        });
      }
    } catch (error) {
      // 即使登出 API 呼叫失敗，也導向登入頁，下一次後端檢查會再次清理逾時 session。
    } finally {
      window.location.href = redirectUrl;
    }
  }

  bars.forEach(({ bar, countdown, secondsAttr, redirectAttr, logoutAttr, logoutMethod, fallbackUrl }) => {
    if (!bar || !countdown) return;

    let remaining = Number(bar.dataset[secondsAttr] || 0);
    const redirectUrl = bar.dataset[redirectAttr] || fallbackUrl;
    const logoutUrl = bar.dataset[logoutAttr] || redirectUrl;
    bar.timeoutState = { remaining, redirectUrl, logoutUrl, logoutMethod, expired: false, timer: null };
    countdown.textContent = format(remaining);

    bar.timeoutState.timer = window.setInterval(() => {
      if (bar.timeoutState.expired) return;
      bar.timeoutState.remaining -= 1;
      countdown.textContent = format(bar.timeoutState.remaining);
      if (bar.timeoutState.remaining <= 0) {
        window.clearInterval(bar.timeoutState.timer);
        expireSession(bar, bar.timeoutState.logoutUrl, bar.timeoutState.logoutMethod, bar.timeoutState.redirectUrl);
      }
    }, 1000);
  });

  const adminBar = document.querySelector('.admin-timeout-bar');
  if (adminBar) {
    let polling = false;
    let touching = false;
    let touchTimer = null;
    const adminCountdown = document.getElementById('admin-timeout-countdown');
    const adminTimeoutTotal = Number(adminBar.dataset.timeoutTotal || adminBar.dataset.timeoutSeconds || 0);

    const resetAdminCountdown = (seconds = adminTimeoutTotal) => {
      if (!adminBar.timeoutState || adminBar.timeoutState.expired || !adminCountdown || !seconds) return;
      adminBar.timeoutState.remaining = Number(seconds);
      adminCountdown.textContent = format(adminBar.timeoutState.remaining);
    };

    const touchAdminSession = async () => {
      if (touching) return;
      touching = true;
      try {
        const res = await fetch(`${APP_BASE}/api/admin/session-touch`, {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
        });
        const data = await res.json().catch(() => ({}));

        if (res.status === 401 || res.status === 409) {
          window.location.href = `${APP_BASE}/admin/login`;
          return;
        }
        if (res.ok && data.remaining_seconds) {
          resetAdminCountdown(data.remaining_seconds);
        }
      } catch (error) {
        // 使用者操作已先重置畫面倒數；若同步失敗，下一次操作會再補送。
      } finally {
        touching = false;
      }
    };

    const scheduleAdminTouch = () => {
      if (adminBar.timeoutState?.expired) return;
      resetAdminCountdown();
      window.clearTimeout(touchTimer);
      touchTimer = window.setTimeout(touchAdminSession, 700);
    };

    ['click', 'keydown', 'input', 'change', 'scroll', 'pointerdown'].forEach((eventName) => {
      window.addEventListener(eventName, scheduleAdminTouch, { passive: true });
    });

    const checkAdminSession = async () => {
      if (polling) return;
      polling = true;
      try {
        const res = await fetch(`${APP_BASE}/api/admin/session-status`, {
          headers: { 'Accept': 'application/json' },
        });
        const data = await res.json().catch(() => ({}));

        if (res.status === 401 || res.status === 409) {
          window.location.href = `${APP_BASE}/admin/login`;
          return;
        }

        if (data.duplicate_login_attempt) {
          const attempt = data.duplicate_login_attempt;
          alert(`警告：有人嘗試使用目前相同的管理帳號登入。\n嘗試登入時間：${attempt.attempt_at || '—'}\nIP Address：${attempt.ip_address || '—'}\n系統已阻止第二個相同身分同時登入。`);
        }
      } catch (error) {
        // 狀態檢查失敗時不打斷目前操作，下一輪輪詢再確認。
      } finally {
        polling = false;
      }
    };

    window.setTimeout(checkAdminSession, 1500);
    window.setInterval(checkAdminSession, 10000);
  }

  const memberBar = document.querySelector('.member-timeout-bar');
  if (memberBar) {
    let memberPolling = false;
    let memberTouching = false;
    let memberTouchTimer = null;
    const memberCountdown = document.getElementById('member-timeout-countdown');
    const memberTimeoutTotal = Number(memberBar.dataset.memberTimeoutTotal || memberBar.dataset.memberTimeout || 0);

    const resetMemberCountdown = (seconds = memberTimeoutTotal) => {
      if (!memberBar.timeoutState || memberBar.timeoutState.expired || !memberCountdown || !seconds) return;
      memberBar.timeoutState.remaining = Number(seconds);
      memberCountdown.textContent = format(memberBar.timeoutState.remaining);
    };

    const touchMemberSession = async () => {
      if (memberTouching) return;
      memberTouching = true;
      try {
        const res = await fetch(`${APP_BASE}/api/members/session-touch`, {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
        });
        const data = await res.json().catch(() => ({}));

        if (res.status === 401 || res.status === 409) {
          window.location.href = `${APP_BASE}/login`;
          return;
        }
        if (res.ok && data.remaining_seconds) {
          resetMemberCountdown(data.remaining_seconds);
        }
      } catch (error) {
        // 使用者操作已先重置畫面倒數；若同步失敗，下一次操作會再補送。
      } finally {
        memberTouching = false;
      }
    };

    const scheduleMemberTouch = () => {
      if (memberBar.timeoutState?.expired) return;
      resetMemberCountdown();
      window.clearTimeout(memberTouchTimer);
      memberTouchTimer = window.setTimeout(touchMemberSession, 700);
    };

    ['click', 'keydown', 'input', 'change', 'scroll', 'pointerdown'].forEach((eventName) => {
      window.addEventListener(eventName, scheduleMemberTouch, { passive: true });
    });

    const checkMemberSession = async () => {
      if (memberPolling) return;
      memberPolling = true;
      try {
        const res = await fetch(`${APP_BASE}/api/members/session-status`, {
          headers: { 'Accept': 'application/json' },
        });
        const data = await res.json().catch(() => ({}));

        if (res.status === 401 || res.status === 409) {
          window.location.href = `${APP_BASE}/login`;
          return;
        }

        if (data.duplicate_login_attempt) {
          const attempt = data.duplicate_login_attempt;
          alert(`警告：有人嘗試使用目前相同的會員帳號登入。\n嘗試登入時間：${attempt.attempt_at || '—'}\nIP Address：${attempt.ip_address || '—'}\n系統已阻止第二個相同身分同時登入。`);
        }
      } catch (error) {
        // 狀態檢查失敗時不打斷目前操作，下一輪輪詢再確認。
      } finally {
        memberPolling = false;
      }
    };

    window.setTimeout(checkMemberSession, 1500);
    window.setInterval(checkMemberSession, 10000);
  }
})();
</script>
</body>
</html>
