/*
 * Yo-gig House Manager - three.js 3D scenes.
 *
 * Progressive enhancement only: every scene sits on top of markup that already
 * works without it (CSS hero images, a plain image grid, the escrow steps).
 * three.js is downloaded only when a page has a 3D target, the browser has
 * WebGL, and the visitor has not asked for reduced motion or data saving.
 *
 * Scenes:
 *   [data-three-hero]     hero photos rendered in 3D with parallax, soap bubbles and sparkles
 *   [data-three-gallery]  draggable 3D ring of the site's own images
 *   [data-three-escrow]   client -> escrow shield -> maid, with gold coins moving between them
 */

const heroes = Array.from(document.querySelectorAll('[data-three-hero]'));
const galleries = Array.from(document.querySelectorAll('[data-three-gallery]'));
const escrows = Array.from(document.querySelectorAll('[data-three-escrow]'));

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const saveData = Boolean(navigator.connection && navigator.connection.saveData);
const isSmall = window.matchMedia('(max-width: 720px)').matches;
const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
const lowPower = (navigator.hardwareConcurrency || 4) <= 2 || (navigator.deviceMemory || 4) <= 1;

function hasWebGL() {
    try {
        const canvas = document.createElement('canvas');
        return Boolean(canvas.getContext('webgl2') || canvas.getContext('webgl'));
    } catch (error) {
        return false;
    }
}

const shouldRun = (heroes.length || galleries.length || escrows.length)
    && !prefersReducedMotion && !saveData && !lowPower && hasWebGL();

if (shouldRun) {
    import(new URL('./vendor/three.module.min.js', import.meta.url).href)
        .then((THREE) => {
            heroes.forEach((el) => whenNear(el, () => safely(() => heroScene(THREE, el))));
            galleries.forEach((el) => whenNear(el, () => safely(() => galleryScene(THREE, el))));
            escrows.forEach((el) => whenNear(el, () => safely(() => escrowScene(THREE, el))));
        })
        .catch(() => { /* Keep the regular page if three.js cannot load. */ });
}

/* ------------------------------------------------------------------ */
/* Shared helpers                                                       */
/* ------------------------------------------------------------------ */

function safely(fn) {
    try {
        fn();
    } catch (error) {
        if (window.console) console.warn('[3D] scene skipped:', error);
    }
}

/** Start a scene only when its element gets close to the viewport. */
function whenNear(el, start) {
    if (!('IntersectionObserver' in window)) return start();
    const observer = new IntersectionObserver((entries) => {
        if (entries.some((e) => e.isIntersecting)) {
            observer.disconnect();
            start();
        }
    }, { rootMargin: '200px 0px' });
    observer.observe(el);
}

function pixelRatio() {
    return Math.min(window.devicePixelRatio || 1, isSmall ? 1.25 : 1.75);
}

/**
 * Create a renderer + canvas in `host`, keep it sized, and run `frame` only
 * while the canvas is on screen and the tab is visible.
 */
function createStage(THREE, host, { linear = true, className = '' } = {}) {
    const renderer = new THREE.WebGLRenderer({
        antialias: !isSmall,
        alpha: true,
        powerPreference: 'low-power',
    });
    renderer.setPixelRatio(pixelRatio());
    renderer.setClearColor(0x000000, 0);
    // Linear output passes colours straight through, so photos and CSS colours
    // look exactly like they do in the stylesheet.
    renderer.outputColorSpace = linear ? THREE.LinearSRGBColorSpace : THREE.SRGBColorSpace;

    const canvas = renderer.domElement;
    canvas.className = ('three-canvas ' + className).trim();
    canvas.setAttribute('aria-hidden', 'true');
    canvas.setAttribute('role', 'presentation');

    const stage = {
        renderer,
        canvas,
        width: 1,
        height: 1,
        visible: true,
        frame: null,
        resize: null,
        clock: new THREE.Clock(),
        raf: 0,
    };

    const measure = () => {
        const rect = canvas.getBoundingClientRect();
        const w = Math.max(1, Math.round(rect.width));
        const h = Math.max(1, Math.round(rect.height));
        if (w === stage.width && h === stage.height) return;
        stage.width = w;
        stage.height = h;
        renderer.setSize(w, h, false);
        if (stage.resize) stage.resize(w, h);
    };

    const loop = () => {
        stage.raf = 0;
        if (!stage.visible || document.hidden) return;
        const dt = Math.min(stage.clock.getDelta(), 0.05);
        if (stage.frame) stage.frame(dt, stage.clock.elapsedTime);
        stage.raf = requestAnimationFrame(loop);
    };

    stage.start = () => {
        if (stage.dead) return;
        measure();
        if (!stage.raf && stage.visible && !document.hidden) {
            stage.clock.getDelta();
            stage.raf = requestAnimationFrame(loop);
        }
    };

    if ('ResizeObserver' in window) {
        new ResizeObserver(measure).observe(canvas);
    } else {
        window.addEventListener('resize', measure);
    }

    if ('IntersectionObserver' in window) {
        new IntersectionObserver((entries) => {
            stage.visible = entries[entries.length - 1].isIntersecting;
            if (stage.visible) stage.start();
        }).observe(host);
    }

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) stage.start();
    });

    return stage;
}

const textureCache = new Map();

/** Load an image as a texture, downscaled for phones to save GPU memory. */
function loadTexture(THREE, url) {
    if (!url) return Promise.reject(new Error('no image'));
    if (textureCache.has(url)) return textureCache.get(url);

    const promise = new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.decoding = 'async';
        img.onload = () => {
            const max = isSmall ? 1024 : 1600;
            const scale = Math.min(1, max / Math.max(img.naturalWidth, img.naturalHeight));
            let source = img;
            if (scale < 1) {
                const c = document.createElement('canvas');
                c.width = Math.round(img.naturalWidth * scale);
                c.height = Math.round(img.naturalHeight * scale);
                c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                source = c;
            }
            const texture = new THREE.Texture(source);
            texture.generateMipmaps = false;
            texture.minFilter = THREE.LinearFilter;
            texture.magFilter = THREE.LinearFilter;
            texture.wrapS = texture.wrapT = THREE.ClampToEdgeWrapping;
            texture.needsUpdate = true;
            texture.userData.aspect = img.naturalWidth / img.naturalHeight;
            resolve(texture);
        };
        img.onerror = () => reject(new Error('image failed: ' + url));
        img.src = url;
    });

    textureCache.set(url, promise);
    return promise;
}

/** Pull the url out of an element's inline `background-image: url(...)`. */
function backgroundUrl(el) {
    if (!el) return null;
    const match = /url\((['"]?)(.*?)\1\)/.exec(el.style.backgroundImage || '');
    return match ? match[2] : null;
}

function cssColor(r, g, b, a) {
    return [r / 255, g / 255, b / 255, a];
}

/* ------------------------------------------------------------------ */
/* Hero: the slide photo in 3D, with bubbles and sparkles                */
/* ------------------------------------------------------------------ */

function heroScene(THREE, host) {
    const isSlider = host.hasAttribute('data-slider');
    const slides = isSlider ? Array.from(host.querySelectorAll('[data-slide]')) : [];
    const urlFor = (i) => backgroundUrl(slides[i] && slides[i].querySelector('.hero__bg'));
    const activeIndex = () => Math.max(0, slides.findIndex((s) => s.classList.contains('is-active')));
    const firstUrl = isSlider ? urlFor(activeIndex()) : backgroundUrl(host);

    const stage = createStage(THREE, host, { className: 'three-canvas--hero' });
    host.insertBefore(stage.canvas, host.firstChild);

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(35, 1, 0.1, 50);
    camera.position.set(0, 0, 6);

    // Same tint as the CSS overlays: diagonal on the home hero, vertical on inner pages.
    const stops = isSlider
        ? [cssColor(8, 40, 49, 0.92), cssColor(15, 76, 92, 0.78), cssColor(15, 76, 92, 0.45)]
        : [cssColor(8, 40, 49, 0.88), cssColor(12, 58, 71, 0.85), cssColor(15, 76, 92, 0.82)];

    const photoUniforms = {
        uTexA: { value: null },
        uTexB: { value: null },
        uAspectA: { value: 1.5 },
        uAspectB: { value: 1.5 },
        uMix: { value: 0 },
        uHasTex: { value: 0 },
        uPlaneAspect: { value: 1 },
        uTime: { value: 0 },
        uResolution: { value: new THREE.Vector2(1, 1) },
        uAngle: { value: (isSlider ? 115 : 180) * Math.PI / 180 },
        uC0: { value: new THREE.Vector4(...stops[0]) },
        uC1: { value: new THREE.Vector4(...stops[1]) },
        uC2: { value: new THREE.Vector4(...stops[2]) },
        uBase: { value: new THREE.Vector3(15 / 255, 76 / 255, 92 / 255) },
    };

    const photo = new THREE.Mesh(
        new THREE.PlaneGeometry(1, 1, 24, 16),
        new THREE.ShaderMaterial({
            uniforms: photoUniforms,
            vertexShader: /* glsl */`
                uniform float uTime;
                varying vec2 vUv;
                void main() {
                    vUv = uv;
                    vec3 p = position;
                    // A slow, gentle cloth-like wave so the photo feels alive.
                    p.z += sin(p.x * 3.0 + uTime * 0.6) * 0.035 + cos(p.y * 4.0 + uTime * 0.45) * 0.03;
                    gl_Position = projectionMatrix * modelViewMatrix * vec4(p, 1.0);
                }
            `,
            fragmentShader: /* glsl */`
                uniform sampler2D uTexA;
                uniform sampler2D uTexB;
                uniform float uAspectA;
                uniform float uAspectB;
                uniform float uMix;
                uniform float uHasTex;
                uniform float uPlaneAspect;
                uniform vec2 uResolution;
                uniform float uAngle;
                uniform vec4 uC0;
                uniform vec4 uC1;
                uniform vec4 uC2;
                uniform vec3 uBase;
                varying vec2 vUv;

                vec2 cover(vec2 uv, float imageAspect) {
                    vec2 scale = uPlaneAspect > imageAspect
                        ? vec2(1.0, imageAspect / uPlaneAspect)
                        : vec2(uPlaneAspect / imageAspect, 1.0);
                    return (uv - 0.5) * scale + 0.5;
                }

                void main() {
                    vec3 photo = uBase;
                    if (uHasTex > 0.5) {
                        vec3 a = texture2D(uTexA, cover(vUv, uAspectA)).rgb;
                        vec3 b = texture2D(uTexB, cover(vUv, uAspectB)).rgb;
                        photo = mix(a, b, smoothstep(0.0, 1.0, uMix));
                    }

                    // CSS linear-gradient(angle, c0, c1 50%, c2) in screen space.
                    vec2 px = vec2(gl_FragCoord.x, uResolution.y - gl_FragCoord.y) - 0.5 * uResolution;
                    vec2 dir = vec2(sin(uAngle), -cos(uAngle));
                    float halfLen = 0.5 * (abs(uResolution.x * dir.x) + abs(uResolution.y * dir.y));
                    float t = clamp(dot(px, dir) / (2.0 * halfLen) + 0.5, 0.0, 1.0);
                    vec4 tint = t < 0.5 ? mix(uC0, uC1, t * 2.0) : mix(uC1, uC2, (t - 0.5) * 2.0);

                    gl_FragColor = vec4(mix(photo, tint.rgb, tint.a), 1.0);
                }
            `,
            depthWrite: false,
        })
    );
    photo.position.z = -1.2;
    scene.add(photo);

    /* Soap bubbles: instanced billboards with a thin-film rainbow rim. */
    const bubbleCount = Math.round((isSmall ? 12 : 26) * (isSlider ? 1 : 0.6));
    const bubbleGeo = new THREE.InstancedBufferGeometry();
    const quad = new THREE.PlaneGeometry(1, 1);
    bubbleGeo.index = quad.index;
    bubbleGeo.setAttribute('position', quad.getAttribute('position'));
    bubbleGeo.setAttribute('uv', quad.getAttribute('uv'));
    const seed = new Float32Array(bubbleCount * 4);
    const base = new Float32Array(bubbleCount * 3);
    for (let i = 0; i < bubbleCount; i++) {
        base[i * 3] = (Math.random() - 0.5) * 2;           // x as a fraction of the view width
        base[i * 3 + 1] = Math.random();                   // starting height (0..1)
        base[i * 3 + 2] = -0.6 + Math.random() * 2.4;      // depth, in front of the photo
        seed[i * 4] = 0.12 + Math.random() * 0.32;         // size
        seed[i * 4 + 1] = 0.05 + Math.random() * 0.07;     // rise speed
        seed[i * 4 + 2] = Math.random() * Math.PI * 2;     // phase
        seed[i * 4 + 3] = Math.random();                   // hue offset
    }
    bubbleGeo.setAttribute('aBase', new THREE.InstancedBufferAttribute(base, 3));
    bubbleGeo.setAttribute('aSeed', new THREE.InstancedBufferAttribute(seed, 4));
    bubbleGeo.instanceCount = bubbleCount;

    const sharedUniforms = {
        uTime: { value: 0 },
        uView: { value: new THREE.Vector2(4, 2.5) },
        uPixelRatio: { value: pixelRatio() },
    };

    const bubbles = new THREE.Mesh(bubbleGeo, new THREE.ShaderMaterial({
        uniforms: sharedUniforms,
        transparent: true,
        depthWrite: false,
        vertexShader: /* glsl */`
            attribute vec3 aBase;
            attribute vec4 aSeed;
            uniform float uTime;
            uniform vec2 uView;
            varying vec2 vUv;
            varying float vHue;
            varying float vFade;
            void main() {
                vUv = uv;
                vHue = aSeed.w;
                float rise = fract(aBase.y + uTime * aSeed.y);
                vec3 center = vec3(
                    aBase.x * uView.x * 0.55 + sin(uTime * 0.7 + aSeed.z) * 0.18,
                    (rise - 0.5) * (uView.y * 1.25),
                    aBase.z
                );
                vFade = smoothstep(0.0, 0.12, rise) * (1.0 - smoothstep(0.85, 1.0, rise));
                float wobble = 1.0 + sin(uTime * 2.0 + aSeed.z) * 0.04;
                vec4 mv = modelViewMatrix * vec4(center, 1.0);
                mv.xy += position.xy * aSeed.x * vec2(wobble, 2.0 - wobble);
                gl_Position = projectionMatrix * mv;
            }
        `,
        fragmentShader: /* glsl */`
            uniform float uTime;
            varying vec2 vUv;
            varying float vHue;
            varying float vFade;
            vec3 rainbow(float h) {
                return 0.5 + 0.5 * cos(6.2831 * (h + vec3(0.0, 0.33, 0.67)));
            }
            void main() {
                vec2 p = vUv * 2.0 - 1.0;
                float r = length(p);
                if (r > 1.0) discard;
                float rim = smoothstep(0.62, 0.98, r) * (1.0 - smoothstep(0.96, 1.0, r));
                float angle = atan(p.y, p.x);
                vec3 film = rainbow(vHue + r * 0.6 + angle * 0.08 + uTime * 0.05);
                float highlight = smoothstep(0.24, 0.0, length(p - vec2(-0.38, 0.42)));
                float body = 0.06;
                vec3 color = mix(vec3(1.0), film, 0.65) * (rim + body) + vec3(highlight);
                float alpha = (rim * 0.75 + body + highlight * 0.9) * vFade;
                gl_FragColor = vec4(color, alpha);
            }
        `,
    }));
    bubbles.frustumCulled = false;
    scene.add(bubbles);

    /* Gold sparkles (matches the site's accent colour). */
    const sparkleCount = isSmall ? 50 : 120;
    const sparklePos = new Float32Array(sparkleCount * 3);
    const sparkleSeed = new Float32Array(sparkleCount * 2);
    for (let i = 0; i < sparkleCount; i++) {
        sparklePos[i * 3] = (Math.random() - 0.5) * 2;
        sparklePos[i * 3 + 1] = (Math.random() - 0.5) * 2;
        sparklePos[i * 3 + 2] = -0.4 + Math.random() * 2.2;
        sparkleSeed[i * 2] = Math.random() * Math.PI * 2;
        sparkleSeed[i * 2 + 1] = 0.6 + Math.random() * 1.6;
    }
    const sparkleGeo = new THREE.BufferGeometry();
    sparkleGeo.setAttribute('position', new THREE.BufferAttribute(sparklePos, 3));
    sparkleGeo.setAttribute('aSeed', new THREE.BufferAttribute(sparkleSeed, 2));
    const sparkles = new THREE.Points(sparkleGeo, new THREE.ShaderMaterial({
        uniforms: sharedUniforms,
        transparent: true,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
        vertexShader: /* glsl */`
            attribute vec2 aSeed;
            uniform float uTime;
            uniform vec2 uView;
            uniform float uPixelRatio;
            varying float vTwinkle;
            void main() {
                vec3 p = vec3(position.x * uView.x * 0.6, position.y * uView.y * 0.6, position.z);
                p.y += sin(uTime * 0.3 + aSeed.x) * 0.08;
                vTwinkle = pow(0.5 + 0.5 * sin(uTime * aSeed.y + aSeed.x), 3.0);
                vec4 mv = modelViewMatrix * vec4(p, 1.0);
                gl_PointSize = (10.0 + 14.0 * vTwinkle) * uPixelRatio * (3.0 / -mv.z);
                gl_Position = projectionMatrix * mv;
            }
        `,
        fragmentShader: /* glsl */`
            varying float vTwinkle;
            void main() {
                vec2 p = gl_PointCoord * 2.0 - 1.0;
                float star = max(0.0, 1.0 - abs(p.x * p.y) * 18.0 - length(p) * 0.9);
                float core = smoothstep(0.35, 0.0, length(p));
                float a = (star * 0.8 + core) * (0.15 + vTwinkle * 0.85);
                gl_FragColor = vec4(vec3(0.89, 0.66, 0.34) + core * 0.4, a);
            }
        `,
    }));
    sparkles.frustumCulled = false;
    scene.add(sparkles);

    /* Sizing: the photo plane always over-fills the view so its edges never show. */
    stage.resize = (w, h) => {
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        const distance = camera.position.z - photo.position.z;
        const viewH = 2 * Math.tan((camera.fov * Math.PI) / 360) * distance;
        const viewW = viewH * camera.aspect;
        photo.scale.set(viewW * 1.16, viewH * 1.16, 1);
        photoUniforms.uPlaneAspect.value = viewW / viewH;
        photoUniforms.uResolution.value.set(w * stage.renderer.getPixelRatio(), h * stage.renderer.getPixelRatio());
        const frontH = 2 * Math.tan((camera.fov * Math.PI) / 360) * camera.position.z;
        sharedUniforms.uView.value.set(frontH * camera.aspect, frontH);
    };

    /* Pointer parallax (desktop) and scroll parallax (everyone). */
    const pointer = { x: 0, y: 0, tx: 0, ty: 0 };
    if (finePointer) {
        host.addEventListener('pointermove', (event) => {
            const rect = host.getBoundingClientRect();
            pointer.tx = ((event.clientX - rect.left) / rect.width - 0.5) * 2;
            pointer.ty = ((event.clientY - rect.top) / rect.height - 0.5) * 2;
        });
        host.addEventListener('pointerleave', () => { pointer.tx = 0; pointer.ty = 0; });
    }

    /* Crossfade between slide photos when the slider changes. */
    let shownUrl = null;
    let fading = false;
    const showUrl = (url, instant) => {
        if (!url || url === shownUrl) return;
        shownUrl = url;
        loadTexture(THREE, url).then((texture) => {
            if (url !== shownUrl) return;
            if (instant || !photoUniforms.uTexA.value) {
                photoUniforms.uTexA.value = texture;
                photoUniforms.uTexB.value = texture;
                photoUniforms.uAspectA.value = photoUniforms.uAspectB.value = texture.userData.aspect;
                photoUniforms.uMix.value = 0;
            } else {
                photoUniforms.uTexB.value = texture;
                photoUniforms.uAspectB.value = texture.userData.aspect;
                photoUniforms.uMix.value = 0;
                fading = true;
            }
            photoUniforms.uHasTex.value = 1;
        }).catch(() => {
            // If the very first photo cannot be used in WebGL, bow out and keep the CSS hero.
            if (!photoUniforms.uTexA.value) {
                stage.dead = true;
                stage.frame = null;
                stage.canvas.remove();
                stage.renderer.dispose();
            }
        });
    };

    if (isSlider && slides.length > 1) {
        const observer = new MutationObserver(() => showUrl(urlFor(activeIndex())));
        slides.forEach((slide) => observer.observe(slide, { attributes: true, attributeFilter: ['class'] }));
        // Warm the cache so slide changes are instant.
        slides.forEach((_, i) => urlFor(i) && loadTexture(THREE, urlFor(i)).catch(() => {}));
    }

    let ready = false;
    const reveal = () => {
        if (ready) return;
        ready = true;
        host.classList.add('is-3d');
    };

    stage.frame = (dt, time) => {
        sharedUniforms.uTime.value = time;
        photoUniforms.uTime.value = time;

        if (fading) {
            photoUniforms.uMix.value = Math.min(1, photoUniforms.uMix.value + dt / 0.9);
            if (photoUniforms.uMix.value >= 1) {
                photoUniforms.uTexA.value = photoUniforms.uTexB.value;
                photoUniforms.uAspectA.value = photoUniforms.uAspectB.value;
                photoUniforms.uMix.value = 0;
                fading = false;
            }
        }

        pointer.x += (pointer.tx - pointer.x) * Math.min(1, dt * 3);
        pointer.y += (pointer.ty - pointer.y) * Math.min(1, dt * 3);

        const rect = host.getBoundingClientRect();
        const scrolled = Math.max(0, Math.min(1, -rect.top / Math.max(1, rect.height)));

        // Slow "Ken Burns" drift plus parallax, so near objects move more than the photo.
        camera.position.x = pointer.x * 0.35 + Math.sin(time * 0.12) * 0.12;
        camera.position.y = -pointer.y * 0.22 + Math.cos(time * 0.1) * 0.08 - scrolled * 0.6;
        camera.position.z = 6 - Math.sin(time * 0.08) * 0.18;
        camera.lookAt(0, -scrolled * 0.6, 0);
        photo.position.y = -scrolled * 0.35;

        stage.renderer.render(scene, camera);
        if (photoUniforms.uHasTex.value || !firstUrl) reveal();
    };

    if (firstUrl) showUrl(firstUrl, true);
    stage.start();
}

/* ------------------------------------------------------------------ */
/* Gallery: a draggable 3D ring of the site's images                     */
/* ------------------------------------------------------------------ */

function galleryScene(THREE, host) {
    const items = Array.from(host.querySelectorAll('[data-gallery-item]')).map((el) => ({
        src: el.getAttribute('data-src') || (el.querySelector('img') || {}).src,
        title: el.getAttribute('data-title') || '',
    })).filter((item) => item.src);
    if (items.length < 3) return;

    const viewport = host.querySelector('[data-gallery-stage]');
    const caption = host.querySelector('[data-gallery-caption]');
    if (!viewport) return;

    const stage = createStage(THREE, host, { className: 'three-canvas--gallery' });
    viewport.appendChild(stage.canvas);

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(36, 1, 0.1, 60);

    const cardW = 1.6;
    const cardH = 1.1;
    const count = items.length;
    const radius = Math.max(2.3, (count * (cardW + 0.35)) / (Math.PI * 2));
    const ring = new THREE.Group();
    scene.add(ring);

    const cardGeo = new THREE.PlaneGeometry(cardW, cardH, 1, 1);
    const cards = items.map((item, i) => {
        const uniforms = {
            uMap: { value: null },
            uHasMap: { value: 0 },
            uImageAspect: { value: 1.5 },
            uCardAspect: { value: cardW / cardH },
            uShade: { value: 1 },
            uReflect: { value: 0 },
        };
        const material = new THREE.ShaderMaterial({
            uniforms,
            transparent: true,
            side: THREE.DoubleSide,
            depthWrite: true,
            vertexShader: /* glsl */`
                varying vec2 vUv;
                void main() {
                    vUv = uv;
                    gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
                }
            `,
            fragmentShader: /* glsl */`
                uniform sampler2D uMap;
                uniform float uHasMap;
                uniform float uImageAspect;
                uniform float uCardAspect;
                uniform float uShade;
                uniform float uReflect;
                varying vec2 vUv;
                float roundedBox(vec2 p, vec2 b, float r) {
                    vec2 q = abs(p) - b + r;
                    return length(max(q, 0.0)) + min(max(q.x, q.y), 0.0) - r;
                }
                void main() {
                    vec2 p = (vUv - 0.5) * vec2(uCardAspect, 1.0);
                    float d = roundedBox(p, vec2(uCardAspect * 0.5, 0.5), 0.07);
                    float mask = 1.0 - smoothstep(-0.006, 0.0, d);
                    vec2 scale = uCardAspect > uImageAspect
                        ? vec2(1.0, uImageAspect / uCardAspect)
                        : vec2(uCardAspect / uImageAspect, 1.0);
                    vec3 color = uHasMap > 0.5
                        ? texture2D(uMap, (vUv - 0.5) * scale + 0.5).rgb
                        : vec3(0.91, 0.95, 0.96);
                    // Thin gold frame on the edge.
                    float frame = smoothstep(-0.018, -0.008, d);
                    color = mix(color, vec3(0.89, 0.66, 0.34), frame * 0.9);
                    color *= uShade;
                    float alpha = mask;
                    if (uReflect > 0.5) alpha *= smoothstep(0.35, 1.0, vUv.y) * 0.22;
                    if (alpha < 0.01) discard;
                    gl_FragColor = vec4(color, alpha);
                }
            `,
        });

        const angle = (i / count) * Math.PI * 2;
        const holder = new THREE.Group();
        holder.position.set(Math.sin(angle) * radius, 0, Math.cos(angle) * radius);
        holder.rotation.y = angle;

        const card = new THREE.Mesh(cardGeo, material);
        holder.add(card);

        const reflection = new THREE.Mesh(cardGeo, material.clone());
        reflection.material.uniforms = {
            ...uniforms,
            uShade: { value: 1 },
            uReflect: { value: 1 },
        };
        reflection.scale.y = -1;
        reflection.position.y = -cardH - 0.06;
        holder.add(reflection);

        ring.add(holder);

        loadTexture(THREE, item.src).then((texture) => {
            uniforms.uMap.value = texture;
            uniforms.uImageAspect.value = texture.userData.aspect;
            uniforms.uHasMap.value = 1;
        }).catch(() => {});

        return { holder, uniforms, reflectionUniforms: reflection.material.uniforms, angle };
    });

    // Soft gold glow on the "floor".
    const floor = new THREE.Mesh(
        new THREE.CircleGeometry(radius * 1.25, 48),
        new THREE.ShaderMaterial({
            transparent: true,
            depthWrite: false,
            vertexShader: 'varying vec2 vUv; void main(){ vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0); }',
            fragmentShader: 'varying vec2 vUv; void main(){ float d = length(vUv - 0.5) * 2.0; gl_FragColor = vec4(0.06, 0.30, 0.36, (1.0 - smoothstep(0.0, 1.0, d)) * 0.18); }',
        })
    );
    floor.rotation.x = -Math.PI / 2;
    floor.position.y = -cardH / 2 - 0.04;
    scene.add(floor);

    stage.resize = (w, h) => {
        camera.aspect = w / h;
        const tan = Math.tan((camera.fov * Math.PI) / 360);
        const fitWidth = (cardW * (isSmall ? 1.3 : 2.0)) / (2 * tan * camera.aspect);
        const fitHeight = (cardH * 1.85) / (2 * tan);
        const distance = Math.max(fitWidth, fitHeight);
        camera.position.set(0, 0.5, radius + distance);
        camera.lookAt(0, -0.1, radius * 0.25);
        camera.updateProjectionMatrix();
    };

    /* Drag / swipe to spin, with inertia. Vertical swipes still scroll the page. */
    let rotation = 0;
    let velocity = 0;
    let dragging = false;
    let lastX = 0;
    let idle = 0;
    const canvas = stage.canvas;
    canvas.style.touchAction = 'pan-y';

    canvas.addEventListener('pointerdown', (event) => {
        dragging = true;
        lastX = event.clientX;
        velocity = 0;
        canvas.setPointerCapture(event.pointerId);
        host.classList.add('is-dragging');
    });
    canvas.addEventListener('pointermove', (event) => {
        if (!dragging) return;
        const dx = event.clientX - lastX;
        lastX = event.clientX;
        const delta = (dx / Math.max(1, stage.width)) * Math.PI * 1.4;
        rotation += delta;
        velocity = delta * 60;
        idle = 0;
    });
    const release = () => {
        dragging = false;
        host.classList.remove('is-dragging');
    };
    canvas.addEventListener('pointerup', release);
    canvas.addEventListener('pointercancel', release);

    // Keyboard support on the wrapper (arrow keys rotate one card).
    host.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
        event.preventDefault();
        velocity = (event.key === 'ArrowLeft' ? 1 : -1) * ((Math.PI * 2) / count) * 3.2;
        idle = 0;
    });

    let front = -1;
    const worldPos = new THREE.Vector3();

    stage.frame = (dt, time) => {
        idle += dt;
        if (!dragging) {
            rotation += velocity * dt;
            velocity *= Math.pow(0.04, dt);
            if (Math.abs(velocity) < 0.02 && idle > 2) rotation -= dt * 0.18; // gentle auto-spin
        }
        ring.rotation.y = rotation;
        ring.position.y = Math.sin(time * 0.8) * 0.04;

        let best = -Infinity;
        let bestIndex = 0;
        cards.forEach((card, i) => {
            card.holder.getWorldPosition(worldPos);
            const facing = worldPos.z / radius; // 1 = front, -1 = back
            const shade = 0.45 + 0.55 * (facing * 0.5 + 0.5);
            card.uniforms.uShade.value = shade;
            card.reflectionUniforms.uShade.value = shade;
            card.holder.position.y = Math.sin(time * 1.1 + i) * 0.03;
            if (facing > best) {
                best = facing;
                bestIndex = i;
            }
        });

        if (bestIndex !== front) {
            front = bestIndex;
            if (caption) caption.textContent = items[front].title;
        }

        stage.renderer.render(scene, camera);
        host.classList.add('is-3d');
    };

    stage.start();
}

/* ------------------------------------------------------------------ */
/* Escrow: client -> shield -> maid, with gold coins                    */
/* ------------------------------------------------------------------ */

function escrowScene(THREE, host) {
    const stageEl = host.querySelector('[data-escrow-stage]') || host;
    const stage = createStage(THREE, host, { linear: false, className: 'three-canvas--escrow' });
    stageEl.appendChild(stage.canvas);
    stage.renderer.toneMapping = THREE.ACESFilmicToneMapping;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(32, 1, 0.1, 50);

    scene.add(new THREE.HemisphereLight(0xffffff, 0x0f4c5c, 1.6));
    const key = new THREE.DirectionalLight(0xffffff, 2.2);
    key.position.set(2, 4, 5);
    scene.add(key);
    const glow = new THREE.PointLight(0xe3a857, 6, 8, 1.6);
    glow.position.set(0, 0.6, 1.6);
    scene.add(glow);

    const gold = new THREE.MeshStandardMaterial({ color: 0xe3a857, metalness: 0.85, roughness: 0.28 });
    const teal = new THREE.MeshStandardMaterial({ color: 0x1b7f94, metalness: 0.2, roughness: 0.35 });
    const white = new THREE.MeshStandardMaterial({ color: 0xf4f7f8, metalness: 0.05, roughness: 0.45 });

    const extrude = (shape, depth) => {
        const geo = new THREE.ExtrudeGeometry(shape, {
            depth, bevelEnabled: true, bevelThickness: 0.05, bevelSize: 0.05, bevelSegments: 3, curveSegments: 16,
        });
        geo.center();
        return geo;
    };

    /* Client: a phone paying in. */
    const client = new THREE.Group();
    const phoneShape = new THREE.Shape();
    const pw = 0.62, ph = 1.1, pr = 0.12;
    phoneShape.moveTo(-pw / 2 + pr, -ph / 2);
    phoneShape.lineTo(pw / 2 - pr, -ph / 2);
    phoneShape.quadraticCurveTo(pw / 2, -ph / 2, pw / 2, -ph / 2 + pr);
    phoneShape.lineTo(pw / 2, ph / 2 - pr);
    phoneShape.quadraticCurveTo(pw / 2, ph / 2, pw / 2 - pr, ph / 2);
    phoneShape.lineTo(-pw / 2 + pr, ph / 2);
    phoneShape.quadraticCurveTo(-pw / 2, ph / 2, -pw / 2, ph / 2 - pr);
    phoneShape.lineTo(-pw / 2, -ph / 2 + pr);
    phoneShape.quadraticCurveTo(-pw / 2, -ph / 2, -pw / 2 + pr, -ph / 2);
    client.add(new THREE.Mesh(extrude(phoneShape, 0.08), white));
    const screen = new THREE.Mesh(new THREE.PlaneGeometry(pw * 0.78, ph * 0.74), teal);
    screen.position.z = 0.1;
    client.add(screen);
    const payDot = new THREE.Mesh(new THREE.CircleGeometry(0.11, 24), gold);
    payDot.position.set(0, -0.05, 0.11);
    client.add(payDot);
    scene.add(client);

    /* Escrow: a shield with a gold check mark. */
    const shield = new THREE.Group();
    const shieldShape = new THREE.Shape();
    shieldShape.moveTo(0, 0.72);
    shieldShape.bezierCurveTo(0.3, 0.6, 0.48, 0.58, 0.6, 0.6);
    shieldShape.bezierCurveTo(0.62, 0.05, 0.45, -0.45, 0, -0.75);
    shieldShape.bezierCurveTo(-0.45, -0.45, -0.62, 0.05, -0.6, 0.6);
    shieldShape.bezierCurveTo(-0.48, 0.58, -0.3, 0.6, 0, 0.72);
    shield.add(new THREE.Mesh(extrude(shieldShape, 0.16), teal));
    const rim = new THREE.Mesh(extrude(shieldShape, 0.05), gold);
    rim.scale.set(1.1, 1.1, 1);
    rim.position.z = -0.08;
    shield.add(rim);
    const check = new THREE.Shape();
    check.moveTo(-0.28, 0.02);
    check.lineTo(-0.08, -0.2);
    check.lineTo(0.3, 0.24);
    check.lineTo(0.22, 0.31);
    check.lineTo(-0.08, -0.04);
    check.lineTo(-0.2, -0.06);
    check.lineTo(-0.28, 0.02);
    const checkMesh = new THREE.Mesh(extrude(check, 0.06), gold);
    checkMesh.position.z = 0.16;
    shield.add(checkMesh);
    scene.add(shield);

    /* Maid: a little house that gets paid. */
    const house = new THREE.Group();
    const houseShape = new THREE.Shape();
    houseShape.moveTo(-0.5, -0.5);
    houseShape.lineTo(0.5, -0.5);
    houseShape.lineTo(0.5, 0.15);
    houseShape.lineTo(0, 0.6);
    houseShape.lineTo(-0.5, 0.15);
    houseShape.lineTo(-0.5, -0.5);
    house.add(new THREE.Mesh(extrude(houseShape, 0.4), white));
    const roofShape = new THREE.Shape();
    roofShape.moveTo(-0.66, 0.1);
    roofShape.lineTo(0, 0.7);
    roofShape.lineTo(0.66, 0.1);
    roofShape.lineTo(0.54, 0.02);
    roofShape.lineTo(0, 0.52);
    roofShape.lineTo(-0.54, 0.02);
    roofShape.lineTo(-0.66, 0.1);
    const roof = new THREE.Mesh(extrude(roofShape, 0.5), teal);
    roof.position.y = 0.33;
    house.add(roof);
    const door = new THREE.Mesh(new THREE.BoxGeometry(0.22, 0.36, 0.05), gold);
    door.position.set(0, -0.3, 0.26);
    house.add(door);
    scene.add(house);

    /* Coins travelling along arcs. */
    const coinGeo = new THREE.CylinderGeometry(0.17, 0.17, 0.05, 32);
    coinGeo.rotateX(Math.PI / 2);
    const coins = [0, 1, 2].map((i) => {
        const coin = new THREE.Mesh(coinGeo, gold);
        coin.userData.offset = i / 3;
        scene.add(coin);
        return coin;
    });

    let spread = 2.6;
    const curveA = new THREE.QuadraticBezierCurve3(new THREE.Vector3(), new THREE.Vector3(), new THREE.Vector3());
    const curveB = new THREE.QuadraticBezierCurve3(new THREE.Vector3(), new THREE.Vector3(), new THREE.Vector3());
    const layout = () => {
        client.position.set(-spread, 0, 0);
        shield.position.set(0, 0, 0);
        house.position.set(spread, 0, 0);
        curveA.v0.set(-spread + 0.2, 0.2, 0.3);
        curveA.v1.set(-spread / 2, 1.3, 0.6);
        curveA.v2.set(-0.2, 0.15, 0.4);
        curveB.v0.set(0.2, 0.15, 0.4);
        curveB.v1.set(spread / 2, 1.3, 0.6);
        curveB.v2.set(spread - 0.2, 0.1, 0.4);
    };

    stage.resize = (w, h) => {
        camera.aspect = w / h;
        spread = camera.aspect < 1.6 ? 1.75 : 2.6;
        layout();
        const tan = Math.tan((camera.fov * Math.PI) / 360);
        const fitWidth = (spread * 2 + 1.6) / (2 * tan * camera.aspect);
        const fitHeight = 2.2 / (2 * tan);
        camera.position.set(0, 0.25, Math.max(fitWidth, fitHeight));
        camera.lookAt(0, 0.1, 0);
        camera.updateProjectionMatrix();
    };

    const cycle = 4.2;
    stage.frame = (dt, time) => {
        client.rotation.y = Math.sin(time * 0.8) * 0.35;
        client.position.y = Math.sin(time * 1.2) * 0.06;
        shield.rotation.y = Math.sin(time * 0.6) * 0.45;
        shield.position.y = Math.sin(time * 1.2 + 1) * 0.06;
        house.rotation.y = -0.35 + Math.sin(time * 0.7) * 0.25;
        house.position.y = Math.sin(time * 1.2 + 2) * 0.06;

        let held = 0;
        coins.forEach((coin) => {
            const t = ((time / cycle) + coin.userData.offset) % 1;
            // 0-0.4: client -> escrow, 0.4-0.6: held in escrow, 0.6-1: escrow -> maid.
            if (t < 0.4) {
                coin.position.copy(curveA.getPoint(easeInOut(t / 0.4)));
                coin.scale.setScalar(1);
            } else if (t < 0.6) {
                held = Math.max(held, Math.sin(((t - 0.4) / 0.2) * Math.PI));
                coin.position.set(0, 0.15 + Math.sin(time * 4) * 0.03, 0.42);
                coin.scale.setScalar(Math.max(0.001, 1 - Math.sin(((t - 0.4) / 0.2) * Math.PI)));
            } else {
                coin.position.copy(curveB.getPoint(easeInOut((t - 0.6) / 0.4)));
                coin.scale.setScalar(t > 0.95 ? (1 - t) / 0.05 : 1);
            }
            coin.rotation.y += dt * 5;
        });

        // The shield glows while it holds the money.
        glow.intensity = 3 + held * 9;
        shield.scale.setScalar(1 + held * 0.06);

        stage.renderer.render(scene, camera);
        host.classList.add('is-3d');
    };

    stage.start();
}

function easeInOut(t) {
    return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
}
