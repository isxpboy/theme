(() => {
'use strict';
const settings = window.LeiThemeSettings || {};
const escapeHTML = value => String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
document.querySelectorAll('.appearance-picker').forEach(picker => {
  const icons = {auto:'<rect x="3" y="4" width="14" height="10" rx="2"/><path d="M7 17h6m-3-3v3"/>',light:'<circle cx="10" cy="10" r="3.2"/><path d="M10 2v1.4M10 16.6V18M2 10h1.4M16.6 10H18M4.3 4.3l1 1m9.4 9.4 1 1M4.3 15.7l1-1m9.4-9.4 1-1"/>',dark:'<path d="M16.8 12.2A7.2 7.2 0 0 1 7.8 3.2a7.2 7.2 0 1 0 9 9Z"/>'};
  const update = () => {
    const mode = window.leiAppearance?.mode || 'auto';
    picker.querySelector('.appearance-icon').innerHTML = '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" aria-hidden="true">'+icons[mode]+'</svg>';
    picker.querySelectorAll('[data-appearance-choice]').forEach(choice=>choice.setAttribute('aria-checked',String(choice.dataset.appearanceChoice===mode)));
  };
  picker.querySelectorAll('[data-appearance-choice]').forEach(choice=>choice.addEventListener('click',()=>{window.leiAppearance?.set(choice.dataset.appearanceChoice);picker.open=false;picker.querySelector('summary').focus();}));
  document.addEventListener('lei:appearance-change',update);
  document.addEventListener('click',event=>{if(!picker.contains(event.target))picker.open=false;});
  picker.addEventListener('keydown',event=>{if(event.key==='Escape'){picker.open=false;picker.querySelector('summary').focus();}});
  update();
});
document.querySelectorAll('.back-to-top').forEach(link=>{
  link.classList.add('is-enhanced');let pending=false;
  const update=()=>{link.classList.toggle('is-visible',window.scrollY>320);pending=false;};
  window.addEventListener('scroll',()=>{if(!pending){pending=true;requestAnimationFrame(update);}},{passive:true});
  link.addEventListener('click',event=>{event.preventDefault();document.dispatchEvent(new Event('lei:manual-scroll'));window.scrollTo({top:0,behavior:'smooth'});});update();
});
if(settings.code)document.querySelectorAll('.wp-block-post-content pre').forEach(pre=>{
  if(pre.closest('.code-block'))return;
  let code=pre.querySelector('code');if(!code){code=document.createElement('code');code.textContent=pre.textContent;pre.replaceChildren(code);}
  const languageClass=[...code.classList,...pre.classList].find(name=>/^language-/.test(name));
  const language=(languageClass||'language-plain').slice(9);
  code.classList.add('language-'+language);
  const names={plain:'纯文本',javascript:'JavaScript',typescript:'TypeScript',yaml:'YAML',bash:'Shell',json:'JSON',python:'Python',php:'PHP',markup:'HTML',css:'CSS',go:'Go',sql:'SQL'};
  const figure=document.createElement('figure');figure.className='code-block';
  figure.innerHTML='<div class="code-toolbar"><span class="code-name">'+escapeHTML(pre.dataset.leiFilename||names[language]||language)+'<span class="code-language">'+escapeHTML(pre.dataset.leiFilename?names[language]||language:'')+'</span></span><button type="button" class="copy-code" aria-label="复制代码"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true"><rect x="6" y="6" width="10" height="11" rx="1.5"/><path d="M12 6V3H3v11h3"/></svg><span aria-live="polite">复制</span></button></div><div class="code-content"></div>';
  const area=figure.querySelector('.code-content');
  if(settings.lineNumbers){const gutter=document.createElement('div');gutter.className='code-line-numbers';gutter.setAttribute('aria-hidden','true');gutter.innerHTML=code.textContent.split('\n').map((_,index)=>'<span>'+(index+1)+'</span>').join('');area.append(gutter);}
  pre.before(figure);area.append(pre);pre.tabIndex=0;
  if(window.Prism?.languages[language])Prism.highlightElement(code);
  const button=figure.querySelector('.copy-code');
  button.addEventListener('click',async()=>{
    if(button.dataset.busy)return;
    try {
      if(navigator.clipboard?.writeText)await navigator.clipboard.writeText(code.textContent);
      else {const field=document.createElement('textarea');field.value=code.textContent;field.className='copy-fallback';document.body.append(field);field.select();const ok=document.execCommand('copy');field.remove();button.focus();if(!ok)throw new Error('copy');}
      button.dataset.busy='1';button.querySelector('span').textContent='已复制';setTimeout(()=>{delete button.dataset.busy;button.querySelector('span').textContent='复制';},2000);
    } catch {button.querySelector('span').textContent='请选中复制';setTimeout(()=>{button.querySelector('span').textContent='复制';},3000);}
  });
});
if(matchMedia('(max-width:700px)').matches)document.querySelectorAll('.article-toc').forEach(toc=>toc.open=false);
if(settings.reading&&document.querySelector('.read-aloud')&&document.querySelector('.wp-block-post-content')&&document.querySelector('.wp-block-post-title'))setupReading({});
  function setupReading(post) {
    const button = document.querySelector('.read-aloud');
    const stopButton = document.querySelector('.stop-reading');
    const status = document.querySelector('.reading-status');
    const synth = window.speechSynthesis;
    if (!synth || !window.SpeechSynthesisUtterance) {
      button.disabled = true;
      button.title = '当前浏览器不支持文章朗读';
      status.textContent = '此浏览器暂不支持朗读';
      return;
    }

    const preferences = { voice: '', rate: 1, follow: true };
    try {
      const saved = JSON.parse(localStorage.getItem('lei-notes-reader-settings') || '{}');
      if (typeof saved.voice === 'string') preferences.voice = saved.voice;
      if ([0.85, 0.95, 1, 1.15, 1.3].includes(saved.rate)) preferences.rate = saved.rate;
      if (typeof saved.follow === 'boolean') preferences.follow = saved.follow;
    } catch { /* Storage is optional. */ }
    const savePreferences = () => { try { localStorage.setItem('lei-notes-reader-settings', JSON.stringify(preferences)); } catch { /* Storage is optional. */ } };

    const options = document.createElement('details');
    options.className = 'reading-options';
    options.innerHTML = `<summary aria-label="朗读设置" title="朗读设置"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" aria-hidden="true"><path d="M4 6h12M4 14h12"/><circle cx="8" cy="6" r="2" fill="var(--paper)"/><circle cx="12" cy="14" r="2" fill="var(--paper)"/></svg></summary><div class="reading-panel"><label>声音<select id="reading-voice" aria-label="朗读声音"></select></label><label>语速<select id="reading-rate" aria-label="朗读语速"><option value="0.85">0.85 倍 · 慢一些</option><option value="0.95">0.95 倍 · 舒缓</option><option value="1">1.0 倍 · 正常</option><option value="1.15">1.15 倍 · 稍快</option><option value="1.3">1.3 倍 · 快速</option></select></label><label class="follow-option"><input type="checkbox" id="reading-follow">平滑跟随朗读位置</label><p>使用设备可用语音，无需付费接口。</p></div>`;
    status.before(options);
    const voiceSelect = options.querySelector('#reading-voice');
    const rateSelect = options.querySelector('#reading-rate');
    const followInput = options.querySelector('#reading-follow');
    rateSelect.value = String(preferences.rate);
    followInput.checked = preferences.follow;
    let availableVoices = [];
    function populateVoices() {
      availableVoices = synth.getVoices().filter((voice) => /^zh/i.test(voice.lang)).sort((a, b) => Number(b.localService) - Number(a.localService));
      voiceSelect.replaceChildren();
      if (!availableVoices.length) voiceSelect.add(new Option('跟随系统默认', ''));
      availableVoices.forEach((voice) => {
        let name = voice.name.replace(/^Microsoft\s+/, '').replace(/\s+Desktop.*$/i, '').replace(/\s*-\s*Chinese.*$/i, '');
        name = name.replace(/Huihui/gi, '慧慧').replace(/Yaoyao/gi, '瑶瑶').replace(/Kangkang/gi, '康康');
        voiceSelect.add(new Option(`${name}${voice.localService ? '' : ' · 在线'}`, voice.voiceURI));
      });
      const selected = availableVoices.find((voice) => voice.voiceURI === preferences.voice) || availableVoices[0];
      if (selected) { preferences.voice = selected.voiceURI; voiceSelect.value = selected.voiceURI; }
    }
    populateVoices();
    synth.addEventListener('voiceschanged', populateVoices);

    const units = [document.querySelector('.wp-block-post-title'), ...document.querySelector('.wp-block-post-content').children].filter((element) => !element.matches('figure, .code-block')).map((element) => {
      const nodes = [];
      let raw = '';
      const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT, { acceptNode: (node) => node.parentElement.closest('.heading-number, figure, pre, button') ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT });
      while (walker.nextNode()) {
        nodes.push({ node: walker.currentNode, start: raw.length, end: raw.length + walker.currentNode.textContent.length });
        raw += walker.currentNode.textContent;
      }
      return { element, nodes, text: raw.trim(), leading: raw.length - raw.trimStart().length };
    }).filter((unit) => unit.text);
    const segments = units.flatMap((unit) => {
      const result = [];
      let offset = 0;
      while (offset < unit.text.length) {
        let end = Math.min(offset + 420, unit.text.length);
        if (end < unit.text.length) {
          const candidate = unit.text.slice(offset, end);
          const boundary = Math.max(...['。', '！', '？', '；', '\n'].map((mark) => candidate.lastIndexOf(mark)));
          if (boundary > 200) end = offset + boundary + 1;
        }
        result.push({ unit, offset, text: unit.text.slice(offset, end) });
        offset = end;
      }
      return result;
    });
    let state = 'idle';
    let index = 0;
    let generation = 0;
    let queued = [];
    let activeElement = null;
    let lastRange = null;
    let scrollFrame = 0;
    let scrollTarget = 0;
    let scrollPosition = 0;
    let lastScrollTime = 0;
    let manualUntil = 0;
    const pauseIcon = '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><rect x="5" y="4" width="3" height="12" rx="1"/><rect x="12" y="4" width="3" height="12" rx="1"/></svg>';
    const playIcon = '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6 3.8a.8.8 0 0 1 1.2-.7l10 6.2a.8.8 0 0 1 0 1.4l-10 6.2a.8.8 0 0 1-1.2-.7Z"/></svg>';
    const dock = document.createElement('div');
    dock.className = 'reading-dock';
    dock.hidden = true;
    dock.setAttribute('role', 'group');
    dock.setAttribute('aria-label', '跟读播放控制');
    dock.innerHTML = '<span class="dock-caption">文章朗读</span><span class="dock-progress"></span><button type="button" class="dock-toggle" aria-label="暂停朗读"></button><button type="button" class="dock-stop" aria-label="停止朗读"><svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><rect x="6" y="6" width="8" height="8" rx="1.5"/></svg></button>';
    document.body.append(dock);
    dock.querySelector('.dock-toggle').addEventListener('click', () => button.click());
    dock.querySelector('.dock-stop').addEventListener('click', () => stopButton.click());
    let controlsVisible = true;
    const observer = new IntersectionObserver(([entry]) => { controlsVisible = entry.isIntersecting; dock.hidden = state === 'idle' || controlsVisible; }, { rootMargin: '-88px 0px 0px 0px' });
    observer.observe(button);

    function cancelScroll() {
      cancelAnimationFrame(scrollFrame);
      scrollFrame = 0;
      scrollTarget = window.scrollY;
      scrollPosition = window.scrollY;
      lastScrollTime = 0;
    }
    function animateScroll(now) {
      if (state !== 'playing' || !preferences.follow || now < manualUntil) { cancelScroll(); return; }
      const elapsed = lastScrollTime ? Math.min(now - lastScrollTime, 40) / 1000 : 1 / 60;
      lastScrollTime = now;
      const remaining = scrollTarget - scrollPosition;
      if (remaining <= 1) { cancelScroll(); return; }
      const speed = Math.min(145, Math.max(28, remaining * 2.4));
      scrollPosition = Math.min(scrollTarget, scrollPosition + speed * elapsed);
      window.scrollTo({ top: scrollPosition, behavior: 'instant' });
      scrollFrame = requestAnimationFrame(animateScroll);
    }
    function followPosition(rect) {
      if (!rect || !preferences.follow || state !== 'playing' || performance.now() < manualUntil) return;
      if (rect.top < window.innerHeight * 0.64) return;
      const maximum = document.documentElement.scrollHeight - window.innerHeight;
      const target = Math.min(maximum, window.scrollY + rect.top - window.innerHeight * 0.43);
      if (target - window.scrollY < 24) return;
      scrollTarget = Math.max(scrollFrame ? scrollTarget : window.scrollY, target);
      if (!scrollFrame) { scrollPosition = window.scrollY; scrollFrame = requestAnimationFrame(animateScroll); }
    }
    const manualScroll = () => { if (state !== 'idle') { manualUntil = performance.now() + 2500; cancelScroll(); } };
    window.addEventListener('wheel', manualScroll, { passive: true });
    window.addEventListener('touchmove', manualScroll, { passive: true });
    document.addEventListener('lei:manual-scroll', manualScroll);
    window.addEventListener('keydown', (event) => { if (['PageDown', 'PageUp', 'ArrowDown', 'ArrowUp', 'Home', 'End', ' '].includes(event.key) && !event.target.closest('input, select, textarea, button, summary')) manualScroll(); });

    function clearHighlight() {
      activeElement?.classList.remove('reading-current');
      activeElement = null;
      lastRange = null;
      if (window.CSS?.highlights) CSS.highlights.delete('reading-position');
    }
    function markPosition(segment, charIndex = 0, charLength = 0) {
      if (activeElement !== segment.unit.element) {
        clearHighlight();
        activeElement = segment.unit.element;
        activeElement.classList.add('reading-current');
      }
      const start = segment.unit.leading + segment.offset + Math.min(charIndex, segment.text.length - 1);
      const suffix = segment.text.slice(charIndex);
      const length = charLength || (suffix.match(/^[A-Za-z0-9_-]+/)?.[0].length ?? 1);
      const end = Math.min(start + length, segment.unit.leading + segment.unit.text.length);
      const startNode = segment.unit.nodes.find((entry) => entry.start <= start && entry.end > start);
      const endNode = segment.unit.nodes.find((entry) => entry.start < end && entry.end >= end);
      if (startNode && endNode) {
        const range = document.createRange();
        range.setStart(startNode.node, start - startNode.start);
        range.setEnd(endNode.node, end - endNode.start);
        lastRange = range;
        if (window.Highlight && window.CSS?.highlights) CSS.highlights.set('reading-position', new Highlight(range));
        followPosition(range.getBoundingClientRect());
      } else followPosition(activeElement.getBoundingClientRect());
    }

    function updateControls() {
      const active = state !== 'idle';
      button.innerHTML = `${state === 'playing' ? pauseIcon : state === 'paused' ? playIcon : speakerIcon}<span>${state === 'playing' ? '暂停' : state === 'paused' ? '继续' : '朗读'}</span>`;
      button.setAttribute('aria-label', state === 'playing' ? '暂停朗读' : state === 'paused' ? '继续朗读' : '朗读文章');
      button.setAttribute('aria-pressed', String(active));
      button.classList.toggle('is-reading', active);
      stopButton.hidden = !active;
      dock.hidden = !active || controlsVisible;
      dock.querySelector('.dock-toggle').innerHTML = state === 'playing' ? pauseIcon : playIcon;
      dock.querySelector('.dock-toggle').setAttribute('aria-label', state === 'playing' ? '暂停朗读' : '继续朗读');
      dock.querySelector('.dock-progress').textContent = `${index + 1} / ${segments.length}`;
    }

    function stopReading(message = '') {
      generation += 1;
      state = 'idle';
      queued = [];
      synth.cancel();
      cancelScroll();
      clearHighlight();
      updateControls();
      status.textContent = message;
    }

    function startQueue(from = 0) {
      generation += 1;
      const token = generation;
      synth.cancel();
      synth.resume();
      const voice = availableVoices.find((item) => item.voiceURI === preferences.voice);
      queued = segments.slice(from).map((segment, offset) => {
        const utterance = new SpeechSynthesisUtterance(segment.text);
        if (voice) utterance.voice = voice;
        utterance.lang = voice?.lang || 'zh-CN';
        utterance.rate = preferences.rate;
        utterance.onstart = () => {
          if (token !== generation) return;
          index = from + offset;
          status.textContent = '正在朗读';
          markPosition(segment);
          updateControls();
        };
        utterance.onboundary = (event) => { if (token === generation && state === 'playing') markPosition(segment, event.charIndex, event.charLength); };
        utterance.onend = () => { if (token === generation && from + offset === segments.length - 1) stopReading('朗读结束'); };
        utterance.onerror = (event) => {
          if (token !== generation || event.error === 'canceled' || event.error === 'interrupted') return;
          stopReading(event.error === 'voice-unavailable' || event.error === 'language-unavailable' ? '当前设备暂无中文朗读语音' : '朗读暂不可用，请重试');
        };
        return utterance;
      });
      queued.forEach((utterance) => synth.speak(utterance));
    }
    function changeSettings() {
      preferences.voice = voiceSelect.value;
      preferences.rate = Number(rateSelect.value);
      savePreferences();
      if (state === 'playing') startQueue(index);
      else if (state === 'paused') { generation += 1; synth.cancel(); queued = []; }
    }
    voiceSelect.addEventListener('change', changeSettings);
    rateSelect.addEventListener('change', changeSettings);
    followInput.addEventListener('change', () => {
      preferences.follow = followInput.checked;
      savePreferences();
      if (!preferences.follow) cancelScroll();
      else if (activeElement) followPosition(lastRange?.getBoundingClientRect() || activeElement.getBoundingClientRect());
    });
    document.addEventListener('click', (event) => { if (!options.contains(event.target)) options.open = false; });
    options.addEventListener('keydown', (event) => { if (event.key === 'Escape') { options.open = false; options.querySelector('summary').focus(); } });

    button.addEventListener('click', () => {
      status.textContent = '';
      if (state === 'playing') {
        synth.pause();
        cancelScroll();
        state = 'paused';
        status.textContent = '已暂停';
      } else if (state === 'paused') {
        state = 'playing';
        if (queued.length) synth.resume();
        else startQueue(index);
        if (activeElement) followPosition(lastRange?.getBoundingClientRect() || activeElement.getBoundingClientRect());
        status.textContent = '正在朗读';
      } else {
        index = 0;
        state = 'playing';
        manualUntil = 0;
        startQueue();
      }
      updateControls();
    });
    stopButton.addEventListener('click', () => stopReading('已停止'));
    window.addEventListener('pagehide', () => stopReading());
  }

if(settings.imageZoom){
 const dialog=document.createElement('dialog');dialog.className='image-dialog';dialog.setAttribute('aria-label','图片放大预览');dialog.innerHTML='<button class="close-dialog" type="button" aria-label="关闭图片预览">关闭 ×</button><img alt="">';document.body.append(dialog);
 dialog.querySelector('button').addEventListener('click',()=>dialog.close());dialog.addEventListener('click',event=>{if(event.target===dialog)dialog.close();});
 document.querySelectorAll('.wp-block-post-content .wp-block-image img,.lei-featured-image img').forEach(image=>{
  if(image.closest('a,[data-wp-interactive],button'))return;
  const button=document.createElement('button');button.type='button';button.className='image-zoom';button.setAttribute('aria-label','放大图片：'+(image.alt||'文章配图'));image.before(button);button.append(image);
  button.addEventListener('click',()=>{const target=dialog.querySelector('img');target.src=image.currentSrc||image.src;target.alt=image.alt;dialog.showModal();});
 });
}
})();
