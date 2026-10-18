<?php
/**
 * One-time success popup. Include where the old inline success banner was.
 * Expects $successMsg (string). Renders nothing when empty.
 */
if (empty($successMsg)) {
    return;
}
?>
<style>
    .sp-overlay{position:fixed;inset:0;background:rgba(45,26,34,.55);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;z-index:100000;padding:20px;animation:spFade .25s ease;}
    .sp-box{background:#fff;border-radius:22px;max-width:420px;width:100%;padding:34px 28px;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.35);animation:spPop .35s cubic-bezier(.2,1.3,.4,1);}
    .sp-tick{width:72px;height:72px;margin:0 auto 14px;border-radius:50%;background:#e9f9ef;color:#1f8a4c;display:flex;align-items:center;justify-content:center;font-size:38px;font-weight:800;}
    .sp-box h3{font-family:'Outfit','Inter',sans-serif;color:#7a1538;font-size:24px;margin-bottom:8px;}
    .sp-box p{color:#6f5b63;font-size:15px;line-height:1.6;margin-bottom:20px;}
    .sp-box button{background:linear-gradient(135deg,#7a1538,#c02455);color:#fff;border:none;border-radius:999px;padding:12px 34px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;}
    @keyframes spFade{from{opacity:0}to{opacity:1}}
    @keyframes spPop{from{opacity:0;transform:scale(.85)}to{opacity:1;transform:none}}
</style>
<div class="sp-overlay" id="spOverlay" role="dialog" aria-modal="true">
    <div class="sp-box">
        <div class="sp-tick">&#10003;</div>
        <h3>Submitted successfully</h3>
        <p><?php echo htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?><?php echo !empty($successNote) ? '<br>' . htmlspecialchars($successNote, ENT_QUOTES, 'UTF-8') : ''; ?></p>
        <button type="button" onclick="document.getElementById('spOverlay').remove()">OK</button>
    </div>
</div>
<script>
    setTimeout(function(){var o=document.getElementById('spOverlay');if(o)o.remove();},8000);
    document.addEventListener('keydown',function(e){if(e.key==='Escape'){var o=document.getElementById('spOverlay');if(o)o.remove();}});
</script>
