/*
 * Yo-gig House Manager - three.js 3D scenes.
 *
 * Progressive enhancement only: every scene sits on top of markup that already
 * works without it (CSS hero images, a plain image grid, the escrow steps).
 * three.js is downloaded only when a page has a 3D target, the browser has
 * WebGL, and the visitor has not asked for reduced motion or data saving.
 *
 * Scenes:
 *   [data-three-hero]     hero photo in 3D: parallax depth, cinematic slide transitions,
 *                         a cursor-following light and a flowing gold particle wave
 *   [data-three-escrow]   studio-lit client -> escrow shield -> maid flow, synced with the steps
 *   [data-three-gallery]  curved 3D carousel of the site's own images with arrows and snapping
 */

const heroes = Array.from(document.querySelectorAll('[data-three-hero]'));
const galleries = Array.from(document.querySelectorAll('[data-three-gallery]'));
const escrows = Array.from(document.querySelectorAll('[data-three-escrow]'));

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const saveData = Boolean(navigator.connection && navigator.connection.saveData);
const isSmall = window.matchMedia('(max-width: 720px)').matches;
const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
const lowPower = (navigator.hardwareConcurrency || 4) <= 2 || (navigator.deviceMemory || 4) <= 1;

const GOLD = [0.89, 0.66, 0.34];

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
            escrows.forEach((el) => whenNear(el, () => safely(() => escrowScene(THREE, el))));
            galleries.forEach((el) => whenNear(el, () => safely(() => galleryScene(THREE, el))));
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
    }, { rootMargin: '250px 0px' });
    observer.observe(el);
}

function maxPixelRatio() {
    return Math.min(window.devicePixelRatio || 1, isSmall ? 1.5 : 2);
}

/**
 * Create a renderer + canvas, keep it sized, run `frame` only while it is on
 * screen and the tab is visible, and lower the resolution automatically if
 * the device cannot keep up (adaptive quality).
 */
function createStage(THREE, host, { linear = true, className = '' } = {}) {
    const renderer = new THREE.WebGLRenderer({
        antialias: !isSmall,
        alpha: true,
        powerPreference: 'high-performance',
    });
    let ratio = maxPixelRatio();
    renderer.setPixelRatio(ratio);
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
        dead: false,
        frame: null,
        resize: null,
        clock: new THREE.Clock(),
        raf: 0,
        quality: 1,
    };

    const measure = (force) => {
        const rect = canvas.getBoundingClientRect();
        const w = Math.max(1, Math.round(rect.width));
        const h = Math.max(1, Math.round(rect.height));
        if (!force && w === stage.width && h === stage.height) return;
        stage.width = w;
        stage.height = h;
        renderer.setSize(w, h, false);
        if (stage.resize) stage.resize(w, h);
    };

    // Adaptive quality: watch the frame time and step the resolution down if needed.
    let samples = 0;
    let total = 0;
    const adapt = (dt) => {
        if (stage.quality <= 0.5) return;
        samples++;
        total += dt;
        if (samples < 60) return;
        const avg = total / samples;
        samples = 0;
        total = 0;
        if (avg > 1 / 40) {
            stage.quality = Math.max(0.5, stage.quality - 0.25);
            ratio = Math.max(0.75, maxPixelRatio() * stage.quality);
            renderer.setPixelRatio(ratio);
            measure(true);
        }
    };

    const loop = () => {
        stage.raf = 0;
        if (!stage.visible || document.hidden || stage.dead) return;
        const dt = Math.min(stage.clock.getDelta(), 0.05);
        if (stage.frame) stage.frame(dt, stage.clock.elapsedTime);
        adapt(dt);
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

    stage.destroy = () => {
        stage.dead = true;
        stage.frame = null;
        canvas.remove();
        renderer.dispose();
    };

    if ('ResizeObserver' in window) {
        new ResizeObserver(() => measure()).observe(canvas);
    } else {
        window.addEventListener('resize', () => measure());
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
            const max = isSmall ? 1280 : 2048;
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

function easeInOut(t) {
    return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
}

function easeOut(t) {
    return 1 - Math.pow(1 - t, 3);
}

function damp(current, target, lambda, dt) {
    return current + (target - current) * (1 - Math.exp(-lambda * dt));
}

/** Pointer position over an element in -1..1, smoothed. Desktop only. */
function trackPointer(el) {
    const p = { x: 0, y: 0, tx: 0, ty: 0, inside: false };
    if (!finePointer) return p;
    el.addEventListener('pointermove', (event) => {
        const rect = el.getBoundingClientRect();
        p.tx = ((event.clientX - rect.left) / rect.width - 0.5) * 2;
        p.ty = ((event.clientY - rect.top) / rect.height - 0.5) * 2;
        p.inside = true;
    });
    el.addEventListener('pointerleave', () => {
        p.tx = 0;
        p.ty = 0;
        p.inside = false;
    });
    p.update = (dt) => {
        p.x = damp(p.x, p.tx, 4, dt);
        p.y = damp(p.y, p.ty, 4, dt);
    };
    return p;
}

const GLSL_NOISE = /* glsl */`
    float hash(vec2 p) { return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453); }
    float noise(vec2 p) {
        vec2 i = floor(p);
        vec2 f = fract(p);
        vec2 u = f * f * (3.0 - 2.0 * f);
        return mix(mix(hash(i), hash(i + vec2(1.0, 0.0)), u.x),
                   mix(hash(i + vec2(0.0, 1.0)), hash(i + vec2(1.0, 1.0)), u.x), u.y);
    }
`;

/* ------------------------------------------------------------------ */
/* Hero                                                                 */
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
    const camera = new THREE.PerspectiveCamera(35, 1, 0.1, 60);
    camera.position.set(0, 0, 6);

    // Same tint as the CSS overlays: diagonal on the home hero, vertical on inner pages.
    const stops = isSlider
        ? [cssColor(8, 40, 49, 0.92), cssColor(15, 76, 92, 0.78), cssColor(15, 76, 92, 0.45)]
        : [cssColor(8, 40, 49, 0.88), cssColor(12, 58, 71, 0.85), cssColor(15, 76, 92, 0.82)];

    const u = {
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
        uLight: { value: new THREE.Vector2(0.72, 0.38) },
        uLightOn: { value: 0 },
        uGold: { value: new THREE.Vector3(...GOLD) },
    };

    const photo = new THREE.Mesh(
        new THREE.PlaneGeometry(1, 1, 1, 1),
        new THREE.ShaderMaterial({
            uniforms: u,
            depthWrite: false,
            vertexShader: /* glsl */`
                varying vec2 vUv;
                void main() {
                    vUv = uv;
                    gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
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
                uniform float uTime;
                uniform vec2 uResolution;
                uniform float uAngle;
                uniform vec4 uC0;
                uniform vec4 uC1;
                uniform vec4 uC2;
                uniform vec3 uBase;
                uniform vec2 uLight;
                uniform float uLightOn;
                uniform vec3 uGold;
                varying vec2 vUv;
                ${GLSL_NOISE}

                vec2 cover(vec2 uv, float imageAspect, float zoom) {
                    vec2 scale = uPlaneAspect > imageAspect
                        ? vec2(1.0, imageAspect / uPlaneAspect)
                        : vec2(uPlaneAspect / imageAspect, 1.0);
                    return (uv - 0.5) * scale / zoom + 0.5;
                }

                void main() {
                    vec3 photo = uBase;
                    float seam = 0.0;
                    if (uHasTex > 0.5) {
                        // Cinematic transition: an organic diagonal wipe with a soft gold edge,
                        // the old photo pushing in while the new one settles.
                        float edge = vUv.x * 0.75 + (1.0 - vUv.y) * 0.25;
                        float n = noise(vUv * 5.0 + uTime * 0.2);
                        float p = uMix * 1.7 - 0.35;
                        float t = smoothstep(edge - 0.12, edge + 0.12, p + (n - 0.5) * 0.22);
                        vec3 a = texture2D(uTexA, cover(vUv, uAspectA, 1.0 + uMix * 0.08)).rgb;
                        vec3 b = texture2D(uTexB, cover(vUv, uAspectB, 1.08 - uMix * 0.08)).rgb;
                        photo = mix(a, b, t);
                        seam = t * (1.0 - t) * 4.0 * step(0.001, uMix) * step(uMix, 0.999);
                    }

                    // CSS linear-gradient(angle, c0, c1 50%, c2) in screen space.
                    vec2 px = vec2(gl_FragCoord.x, uResolution.y - gl_FragCoord.y) - 0.5 * uResolution;
                    vec2 dir = vec2(sin(uAngle), -cos(uAngle));
                    float halfLen = 0.5 * (abs(uResolution.x * dir.x) + abs(uResolution.y * dir.y));
                    float g = clamp(dot(px, dir) / (2.0 * halfLen) + 0.5, 0.0, 1.0);
                    vec4 tint = g < 0.5 ? mix(uC0, uC1, g * 2.0) : mix(uC1, uC2, (g - 0.5) * 2.0);

                    vec3 color = mix(photo, tint.rgb, tint.a);

                    // Soft studio light that follows the cursor.
                    vec2 screenUv = gl_FragCoord.xy / uResolution;
                    vec2 d = (screenUv - uLight) * vec2(uResolution.x / uResolution.y, 1.0);
                    float light = exp(-dot(d, d) * 3.2);
                    color += (photo * 0.22 + uGold * 0.05) * light * (0.55 + 0.45 * uLightOn);

                    // Vignette, gold transition seam and fine film grain.
                    float vig = smoothstep(1.25, 0.35, length((screenUv - 0.5) * vec2(1.2, 1.0)));
                    color *= mix(0.82, 1.0, vig);
                    color += uGold * seam * 0.35;
                    color += (hash(gl_FragCoord.xy + fract(uTime) * 100.0) - 0.5) * 0.028;

                    gl_FragColor = vec4(color, 1.0);
                }
            `,
        })
    );
    photo.position.z = -1.2;
    scene.add(photo);

    /* Flowing gold particle wave: a field of fine dots rolling like fabric. */
    const shared = {
        uTime: { value: 0 },
        uView: { value: new THREE.Vector2(4, 2.5) },
        uPixelRatio: { value: stage.renderer.getPixelRatio() },
        uPointer: { value: new THREE.Vector2(0, 0) },
        uGold: { value: new THREE.Vector3(...GOLD) },
    };

    const cols = isSmall ? 70 : 150;
    const rows = isSmall ? 26 : 44;
    const wavePos = new Float32Array(cols * rows * 3);
    let k = 0;
    for (let r = 0; r < rows; r++) {
        for (let c = 0; c < cols; c++) {
            wavePos[k++] = c / (cols - 1) * 2 - 1;
            wavePos[k++] = 0;
            wavePos[k++] = r / (rows - 1);
        }
    }
    const waveGeo = new THREE.BufferGeometry();
    waveGeo.setAttribute('position', new THREE.BufferAttribute(wavePos, 3));
    const wave = new THREE.Points(waveGeo, new THREE.ShaderMaterial({
        uniforms: shared,
        transparent: true,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
        vertexShader: /* glsl */`
            uniform float uTime;
            uniform vec2 uView;
            uniform float uPixelRatio;
            uniform vec2 uPointer;
            varying float vAlpha;
            varying float vLift;
            void main() {
                float x = position.x * uView.x * 0.8;
                float z = mix(2.4, -2.2, position.z);
                float t = uTime;
                float y = sin(x * 0.75 + t * 0.55) * 0.22
                        + sin(z * 1.1 + t * 0.4 + x * 0.25) * 0.18
                        + sin((x - z) * 1.9 + t * 0.85) * 0.05;
                // A gentle swell under the cursor.
                float dp = distance(vec2(x, z), vec2(uPointer.x * uView.x * 0.5, 0.4));
                y += exp(-dp * dp * 0.6) * 0.18 * length(uPointer);
                vLift = y;
                vec3 p = vec3(x, y - uView.y * 0.4, z);
                vec4 mv = modelViewMatrix * vec4(p, 1.0);
                gl_PointSize = 2.8 * uPixelRatio * (6.0 / -mv.z);
                gl_Position = projectionMatrix * mv;
                float edgeX = 1.0 - smoothstep(0.55, 1.0, abs(position.x));
                float depth = smoothstep(0.0, 0.25, position.z) * (1.0 - smoothstep(0.35, 0.75, position.z));
                vAlpha = edgeX * depth;
            }
        `,
        fragmentShader: /* glsl */`
            uniform vec3 uGold;
            varying float vAlpha;
            varying float vLift;
            void main() {
                float d = length(gl_PointCoord - 0.5);
                if (d > 0.5) discard;
                float soft = smoothstep(0.5, 0.0, d);
                float shine = 0.55 + clamp(vLift * 2.0, -0.3, 0.6);
                gl_FragColor = vec4(uGold * shine, soft * vAlpha * 0.95);
            }
        `,
    }));
    wave.frustumCulled = false;
    scene.add(wave);

    /* A few soft out-of-focus light motes for depth. */
    const moteCount = isSmall ? 14 : 28;
    const motePos = new Float32Array(moteCount * 3);
    const moteSeed = new Float32Array(moteCount * 2);
    for (let i = 0; i < moteCount; i++) {
        motePos[i * 3] = Math.random() * 2 - 1;
        motePos[i * 3 + 1] = Math.random() * 2 - 1;
        motePos[i * 3 + 2] = Math.random() * 2.6 - 0.6;
        moteSeed[i * 2] = Math.random() * Math.PI * 2;
        moteSeed[i * 2 + 1] = 0.4 + Math.random();
    }
    const moteGeo = new THREE.BufferGeometry();
    moteGeo.setAttribute('position', new THREE.BufferAttribute(motePos, 3));
    moteGeo.setAttribute('aSeed', new THREE.BufferAttribute(moteSeed, 2));
    const motes = new THREE.Points(moteGeo, new THREE.ShaderMaterial({
        uniforms: shared,
        transparent: true,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
        vertexShader: /* glsl */`
            attribute vec2 aSeed;
            uniform float uTime;
            uniform vec2 uView;
            uniform float uPixelRatio;
            varying float vAlpha;
            void main() {
                vec3 p = vec3(position.x * uView.x * 0.6, position.y * uView.y * 0.55, position.z);
                p.x += sin(uTime * 0.12 * aSeed.y + aSeed.x) * 0.25;
                p.y += cos(uTime * 0.1 * aSeed.y + aSeed.x) * 0.18;
                vec4 mv = modelViewMatrix * vec4(p, 1.0);
                gl_PointSize = (14.0 + 26.0 * aSeed.y) * uPixelRatio * (3.0 / -mv.z);
                vAlpha = 0.05 + 0.07 * (0.5 + 0.5 * sin(uTime * 0.5 * aSeed.y + aSeed.x));
                gl_Position = projectionMatrix * mv;
            }
        `,
        fragmentShader: /* glsl */`
            uniform vec3 uGold;
            varying float vAlpha;
            void main() {
                float d = length(gl_PointCoord - 0.5);
                float a = smoothstep(0.5, 0.15, d) * vAlpha;
                gl_FragColor = vec4(mix(vec3(1.0), uGold, 0.5), a);
            }
        `,
    }));
    motes.frustumCulled = false;
    scene.add(motes);

    /* Sizing: the photo plane always over-fills the view so its edges never show. */
    stage.resize = (w, h) => {
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        const distance = camera.position.z - photo.position.z;
        const viewH = 2 * Math.tan((camera.fov * Math.PI) / 360) * distance;
        const viewW = viewH * camera.aspect;
        photo.scale.set(viewW * 1.18, viewH * 1.18, 1);
        u.uPlaneAspect.value = viewW / viewH;
        const pr = stage.renderer.getPixelRatio();
        u.uResolution.value.set(w * pr, h * pr);
        shared.uPixelRatio.value = pr;
        const frontH = 2 * Math.tan((camera.fov * Math.PI) / 360) * camera.position.z;
        shared.uView.value.set(frontH * camera.aspect, frontH);
    };

    const pointer = trackPointer(host);

    /* Slide transitions. */
    let shownUrl = null;
    let fading = false;
    const showUrl = (url, instant) => {
        if (!url || url === shownUrl) return;
        shownUrl = url;
        loadTexture(THREE, url).then((texture) => {
            if (url !== shownUrl) return;
            if (instant || !u.uTexA.value) {
                u.uTexA.value = u.uTexB.value = texture;
                u.uAspectA.value = u.uAspectB.value = texture.userData.aspect;
                u.uMix.value = 0;
            } else {
                u.uTexB.value = texture;
                u.uAspectB.value = texture.userData.aspect;
                u.uMix.value = 0;
                fading = true;
            }
            u.uHasTex.value = 1;
        }).catch(() => {
            // If the very first photo cannot be used in WebGL, bow out and keep the CSS hero.
            if (!u.uTexA.value) stage.destroy();
        });
    };

    if (isSlider && slides.length > 1) {
        const observer = new MutationObserver(() => showUrl(urlFor(activeIndex())));
        slides.forEach((slide) => observer.observe(slide, { attributes: true, attributeFilter: ['class'] }));
        slides.forEach((_, i) => urlFor(i) && loadTexture(THREE, urlFor(i)).catch(() => {}));
    }

    let ready = false;
    let fadeT = 0;
    stage.frame = (dt, time) => {
        shared.uTime.value = time;
        u.uTime.value = time;

        if (fading) {
            fadeT = Math.min(1, fadeT + dt / 1.4);
            u.uMix.value = easeInOut(fadeT);
            if (fadeT >= 1) {
                u.uTexA.value = u.uTexB.value;
                u.uAspectA.value = u.uAspectB.value;
                u.uMix.value = 0;
                fadeT = 0;
                fading = false;
            }
        }

        if (pointer.update) pointer.update(dt);
        shared.uPointer.value.set(pointer.x, pointer.y);
        u.uLightOn.value = damp(u.uLightOn.value, pointer.inside ? 1 : 0, 3, dt);
        u.uLight.value.set(
            damp(u.uLight.value.x, pointer.inside ? pointer.tx * 0.5 + 0.5 : 0.72 + Math.sin(time * 0.2) * 0.08, 3, dt),
            damp(u.uLight.value.y, pointer.inside ? 0.5 - pointer.ty * 0.5 : 0.62 + Math.cos(time * 0.17) * 0.06, 3, dt)
        );

        const rect = host.getBoundingClientRect();
        const scrolled = Math.max(0, Math.min(1, -rect.top / Math.max(1, rect.height)));

        // Slow dolly plus parallax: the photo, particles and camera move at different depths.
        camera.position.x = pointer.x * 0.32 + Math.sin(time * 0.11) * 0.1;
        camera.position.y = -pointer.y * 0.18 + Math.cos(time * 0.09) * 0.06 - scrolled * 0.5;
        camera.position.z = 6 - Math.sin(time * 0.07) * 0.2 - scrolled * 0.6;
        camera.lookAt(0, -scrolled * 0.5, 0);
        photo.position.y = -scrolled * 0.4;

        stage.renderer.render(scene, camera);
        if (!ready && (u.uHasTex.value || !firstUrl)) {
            ready = true;
            host.classList.add('is-3d');
        }
    };

    if (firstUrl) showUrl(firstUrl, true);
    stage.start();
}

/* ------------------------------------------------------------------ */
/* Escrow flow                                                          */
/* ------------------------------------------------------------------ */

/** A soft studio light box, pre-filtered for realistic metal and glass reflections. */
function studioEnvironment(THREE, renderer) {
    const env = new THREE.Scene();
    const room = new THREE.Mesh(
        new THREE.SphereGeometry(10, 32, 16),
        new THREE.ShaderMaterial({
            side: THREE.BackSide,
            vertexShader: 'varying vec3 vP; void main(){ vP = position; gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0); }',
            fragmentShader: `varying vec3 vP; void main(){
                float h = normalize(vP).y * 0.5 + 0.5;
                vec3 c = mix(vec3(0.02, 0.10, 0.12), vec3(0.75, 0.85, 0.88), smoothstep(0.2, 1.0, h));
                gl_FragColor = vec4(c, 1.0);
            }`,
        })
    );
    env.add(room);
    const panel = (w, h, color, strength, pos) => {
        const m = new THREE.Mesh(
            new THREE.PlaneGeometry(w, h),
            new THREE.MeshBasicMaterial({ color: new THREE.Color(color).multiplyScalar(strength), side: THREE.DoubleSide })
        );
        m.position.copy(pos);
        m.lookAt(0, 0, 0);
        env.add(m);
    };
    panel(8, 3, 0xffffff, 3.5, new THREE.Vector3(0, 7, 2));      // top softbox
    panel(3, 6, 0xffe2b3, 2.5, new THREE.Vector3(-7, 1, 3));     // warm key
    panel(3, 6, 0xbfe8f0, 1.6, new THREE.Vector3(7, 0, 2));      // cool fill
    panel(6, 2, 0xffffff, 1.2, new THREE.Vector3(0, 1, -7));     // rim
    const pmrem = new THREE.PMREMGenerator(renderer);
    const texture = pmrem.fromScene(env, 0.03).texture;
    pmrem.dispose();
    return texture;
}

function roundedRectShape(THREE, w, h, r) {
    const s = new THREE.Shape();
    s.moveTo(-w / 2 + r, -h / 2);
    s.lineTo(w / 2 - r, -h / 2);
    s.quadraticCurveTo(w / 2, -h / 2, w / 2, -h / 2 + r);
    s.lineTo(w / 2, h / 2 - r);
    s.quadraticCurveTo(w / 2, h / 2, w / 2 - r, h / 2);
    s.lineTo(-w / 2 + r, h / 2);
    s.quadraticCurveTo(-w / 2, h / 2, -w / 2, h / 2 - r);
    s.lineTo(-w / 2, -h / 2 + r);
    s.quadraticCurveTo(-w / 2, -h / 2, -w / 2 + r, -h / 2);
    return s;
}

/** A minimal payment-app screen drawn once onto a texture. */
function phoneScreenTexture(THREE) {
    const c = document.createElement('canvas');
    c.width = 256;
    c.height = 448;
    const g = c.getContext('2d');
    const bg = g.createLinearGradient(0, 0, 0, c.height);
    bg.addColorStop(0, '#13606F');
    bg.addColorStop(1, '#082831');
    g.fillStyle = bg;
    g.fillRect(0, 0, c.width, c.height);
    const pill = (x, y, w, h, color) => {
        g.fillStyle = color;
        g.beginPath();
        g.roundRect ? g.roundRect(x, y, w, h, h / 2) : g.rect(x, y, w, h);
        g.fill();
    };
    pill(28, 36, 90, 12, 'rgba(255,255,255,0.35)');
    // Shield badge
    g.fillStyle = 'rgba(227,168,87,0.18)';
    g.beginPath();
    g.arc(128, 150, 58, 0, Math.PI * 2);
    g.fill();
    g.strokeStyle = '#E3A857';
    g.lineWidth = 9;
    g.lineCap = 'round';
    g.lineJoin = 'round';
    g.beginPath();
    g.moveTo(102, 152);
    g.lineTo(121, 171);
    g.lineTo(156, 132);
    g.stroke();
    pill(58, 236, 140, 16, 'rgba(255,255,255,0.85)');
    pill(78, 266, 100, 10, 'rgba(255,255,255,0.35)');
    pill(28, 316, 200, 10, 'rgba(255,255,255,0.15)');
    pill(28, 338, 160, 10, 'rgba(255,255,255,0.15)');
    pill(28, 380, 200, 40, '#E3A857');
    const texture = new THREE.CanvasTexture(c);
    texture.colorSpace = THREE.SRGBColorSpace;
    return texture;
}

function contactShadow(THREE, size) {
    const mesh = new THREE.Mesh(
        new THREE.PlaneGeometry(size, size * 0.45),
        new THREE.ShaderMaterial({
            transparent: true,
            depthWrite: false,
            uniforms: { uStrength: { value: 0.45 } },
            vertexShader: 'varying vec2 vUv; void main(){ vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0); }',
            fragmentShader: 'uniform float uStrength; varying vec2 vUv; void main(){ vec2 p = (vUv - 0.5) * 2.0; float a = exp(-dot(p,p) * 2.6) * uStrength; gl_FragColor = vec4(0.0, 0.05, 0.07, a); }',
        })
    );
    mesh.rotation.x = -Math.PI / 2;
    return mesh;
}

function escrowScene(THREE, host) {
    const stageEl = host.querySelector('[data-escrow-stage]') || host;
    const labels = Array.from(host.querySelectorAll('[data-escrow-label]'));
    const band = host.closest('.band');
    const steps = band ? Array.from(band.querySelectorAll('.band-step')) : [];

    const stage = createStage(THREE, host, { linear: false, className: 'three-canvas--escrow' });
    stageEl.appendChild(stage.canvas);
    const renderer = stage.renderer;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05;

    const scene = new THREE.Scene();
    scene.environment = studioEnvironment(THREE, renderer);
    const camera = new THREE.PerspectiveCamera(30, 1, 0.1, 50);

    const key = new THREE.DirectionalLight(0xffffff, 1.4);
    key.position.set(3, 5, 6);
    scene.add(key);
    const glow = new THREE.PointLight(0xe3a857, 0, 6, 1.8);
    glow.position.set(0, 0.2, 1.4);
    scene.add(glow);

    const gold = new THREE.MeshPhysicalMaterial({ color: 0xe3a857, metalness: 1, roughness: 0.22, clearcoat: 0.6, clearcoatRoughness: 0.2 });
    const tealGloss = new THREE.MeshPhysicalMaterial({ color: 0x0f4c5c, metalness: 0.15, roughness: 0.18, clearcoat: 1, clearcoatRoughness: 0.08 });
    const ceramic = new THREE.MeshPhysicalMaterial({ color: 0xf3f6f7, metalness: 0, roughness: 0.32, clearcoat: 0.8, clearcoatRoughness: 0.15 });
    const graphite = new THREE.MeshPhysicalMaterial({ color: 0x1b2328, metalness: 0.6, roughness: 0.3, clearcoat: 1, clearcoatRoughness: 0.1 });
    const windowGlow = new THREE.MeshStandardMaterial({ color: 0xffe7b8, emissive: 0xe3a857, emissiveIntensity: 0.35, roughness: 0.4 });

    const extrude = (shape, depth, bevel = 0.04) => {
        const geo = new THREE.ExtrudeGeometry(shape, {
            depth, bevelEnabled: true, bevelThickness: bevel, bevelSize: bevel, bevelSegments: 5, curveSegments: 24,
        });
        geo.center();
        return geo;
    };

    /* Client: a smartphone paying in. */
    const client = new THREE.Group();
    const body = new THREE.Mesh(extrude(roundedRectShape(THREE, 0.68, 1.3, 0.14), 0.07, 0.03), graphite);
    client.add(body);
    const screen = new THREE.Mesh(
        new THREE.ShapeGeometry(roundedRectShape(THREE, 0.6, 1.2, 0.1), 12),
        new THREE.MeshPhysicalMaterial({ map: phoneScreenTexture(THREE), roughness: 0.05, clearcoat: 1, emissive: 0xffffff, emissiveIntensity: 0.25 })
    );
    // ShapeGeometry UVs are in shape units; remap to 0..1 for the texture.
    const uv = screen.geometry.attributes.uv;
    for (let i = 0; i < uv.count; i++) uv.setXY(i, uv.getX(i) / 0.6 + 0.5, uv.getY(i) / 1.2 + 0.5);
    screen.material.emissiveMap = screen.material.map;
    screen.position.z = 0.068;
    client.add(screen);
    scene.add(client);

    /* Escrow: a lacquered shield with a gold rim and check. */
    const shield = new THREE.Group();
    const shieldShape = new THREE.Shape();
    shieldShape.moveTo(0, 0.78);
    shieldShape.bezierCurveTo(0.32, 0.64, 0.5, 0.62, 0.64, 0.64);
    shieldShape.bezierCurveTo(0.66, 0.05, 0.48, -0.48, 0, -0.8);
    shieldShape.bezierCurveTo(-0.48, -0.48, -0.66, 0.05, -0.64, 0.64);
    shieldShape.bezierCurveTo(-0.5, 0.62, -0.32, 0.64, 0, 0.78);
    shield.add(new THREE.Mesh(extrude(shieldShape, 0.14, 0.06), tealGloss));
    const rim = new THREE.Mesh(extrude(shieldShape, 0.06, 0.03), gold);
    rim.scale.set(1.09, 1.08, 1);
    rim.position.z = -0.07;
    shield.add(rim);
    const check = new THREE.Shape();
    check.moveTo(-0.32, 0.04);
    check.lineTo(-0.08, -0.22);
    check.lineTo(0.34, 0.2);
    check.lineTo(0.25, 0.29);
    check.lineTo(-0.08, -0.04);
    check.lineTo(-0.23, 0.13);
    check.lineTo(-0.32, 0.04);
    const checkMesh = new THREE.Mesh(extrude(check, 0.05, 0.02), gold);
    checkMesh.position.set(0, 0.02, 0.14);
    shield.add(checkMesh);
    scene.add(shield);

    /* Maid: a home with a warm lit window. */
    const house = new THREE.Group();
    const walls = new THREE.Shape();
    walls.moveTo(-0.5, -0.5);
    walls.lineTo(0.5, -0.5);
    walls.lineTo(0.5, 0.2);
    walls.lineTo(0, 0.62);
    walls.lineTo(-0.5, 0.2);
    walls.lineTo(-0.5, -0.5);
    house.add(new THREE.Mesh(extrude(walls, 0.5, 0.03), ceramic));
    const roofShape = new THREE.Shape();
    roofShape.moveTo(-0.68, 0.12);
    roofShape.lineTo(0, 0.72);
    roofShape.lineTo(0.68, 0.12);
    roofShape.lineTo(0.56, 0.03);
    roofShape.lineTo(0, 0.54);
    roofShape.lineTo(-0.56, 0.03);
    roofShape.lineTo(-0.68, 0.12);
    const roof = new THREE.Mesh(extrude(roofShape, 0.62, 0.03), tealGloss);
    roof.position.y = 0.33;
    house.add(roof);
    const door = new THREE.Mesh(extrude(roundedRectShape(THREE, 0.22, 0.36, 0.06), 0.04, 0.01), gold);
    door.position.set(-0.15, -0.3, 0.28);
    house.add(door);
    const win = new THREE.Mesh(extrude(roundedRectShape(THREE, 0.2, 0.2, 0.03), 0.03, 0.01), windowGlow);
    win.position.set(0.2, -0.12, 0.28);
    house.add(win);
    scene.add(house);

    const shadows = [contactShadow(THREE, 1.6), contactShadow(THREE, 1.9), contactShadow(THREE, 1.9)];
    shadows.forEach((s) => scene.add(s));

    /* Coin: lathe-turned with a raised rim. */
    const profile = [
        [0, -0.028], [0.15, -0.028], [0.172, -0.02], [0.176, 0], [0.172, 0.02], [0.15, 0.028],
        [0.128, 0.028], [0.12, 0.016], [0, 0.016],
    ].map(([x, y]) => new THREE.Vector2(x, y));
    const coinGeo = new THREE.LatheGeometry(profile, 48);
    coinGeo.rotateX(Math.PI / 2);
    const coin = new THREE.Group();
    coin.add(new THREE.Mesh(coinGeo, gold));
    const back = new THREE.Mesh(coinGeo, gold);
    back.rotation.y = Math.PI;
    coin.add(back);
    scene.add(coin);

    /* Animated dashed routes between the three stations. */
    const routeMat = new THREE.ShaderMaterial({
        transparent: true,
        depthWrite: false,
        uniforms: { uTime: { value: 0 }, uGold: { value: new THREE.Vector3(...GOLD) } },
        vertexShader: 'varying vec2 vUv; void main(){ vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0); }',
        fragmentShader: `uniform float uTime; uniform vec3 uGold; varying vec2 vUv;
            void main(){
                float dash = step(0.45, fract(vUv.x * 26.0 - uTime * 0.9));
                float ends = smoothstep(0.0, 0.08, vUv.x) * smoothstep(1.0, 0.92, vUv.x);
                gl_FragColor = vec4(uGold, dash * ends * 0.55);
            }`,
    });
    const curveA = new THREE.QuadraticBezierCurve3(new THREE.Vector3(), new THREE.Vector3(), new THREE.Vector3());
    const curveB = new THREE.QuadraticBezierCurve3(new THREE.Vector3(), new THREE.Vector3(), new THREE.Vector3());
    let routeA = null;
    let routeB = null;

    /* Pulse rings for "received" moments. */
    const ringGeo = new THREE.RingGeometry(0.9, 0.93, 64);
    const makeRing = () => {
        const ring = new THREE.Mesh(ringGeo, new THREE.MeshBasicMaterial({ color: 0xe3a857, transparent: true, opacity: 0, depthWrite: false }));
        scene.add(ring);
        return ring;
    };
    const shieldRing = makeRing();
    const houseRing = makeRing();

    let spread = 2.6;
    const layout = () => {
        client.position.set(-spread, 0, 0);
        shield.position.set(0, 0, 0);
        house.position.set(spread, -0.02, 0);
        shadows[0].position.set(-spread, -0.92, 0);
        shadows[1].position.set(0, -0.92, 0);
        shadows[2].position.set(spread, -0.92, 0);
        shieldRing.position.set(0, 0, 0.2);
        houseRing.position.set(spread, 0.05, 0.4);
        curveA.v0.set(-spread + 0.3, 0.25, 0.25);
        curveA.v1.set(-spread / 2, 1.25, 0.7);
        curveA.v2.set(-0.05, 0.1, 0.45);
        curveB.v0.set(0.05, 0.1, 0.45);
        curveB.v1.set(spread / 2, 1.25, 0.7);
        curveB.v2.set(spread - 0.25, 0.1, 0.45);
        [routeA, routeB].forEach((r) => { if (r) { scene.remove(r); r.geometry.dispose(); } });
        routeA = new THREE.Mesh(new THREE.TubeGeometry(curveA, 80, 0.008, 6, false), routeMat);
        routeB = new THREE.Mesh(new THREE.TubeGeometry(curveB, 80, 0.008, 6, false), routeMat);
        scene.add(routeA, routeB);
    };

    stage.resize = (w, h) => {
        camera.aspect = w / h;
        const next = camera.aspect < 1.6 ? 1.8 : 2.6;
        if (next !== spread || !routeA) {
            spread = next;
            layout();
        }
        const tan = Math.tan((camera.fov * Math.PI) / 360);
        const fitWidth = (spread * 2 + 1.7) / (2 * tan * camera.aspect);
        const fitHeight = 2.3 / (2 * tan);
        camera.userData.distance = Math.max(fitWidth, fitHeight);
        camera.updateProjectionMatrix();
    };

    const pointer = trackPointer(host.closest('.band') || host);
    const cycle = 6.5;
    let activeStep = -1;
    const setStep = (index) => {
        if (index === activeStep) return;
        activeStep = index;
        labels.forEach((el, i) => el.classList.toggle('is-active', i === index));
        steps.forEach((el, i) => el.classList.toggle('is-active', i === index));
    };
    if (steps.length) band.classList.add('has-escrow-3d');

    stage.frame = (dt, time) => {
        if (pointer.update) pointer.update(dt);
        const d = camera.userData.distance || 8;
        camera.position.set(pointer.x * 0.6, 0.35 - pointer.y * 0.25, d);
        camera.lookAt(0, 0.05, 0);

        routeMat.uniforms.uTime.value = time;

        // Idle motion: everything floats gently and turns towards the viewer.
        client.rotation.set(0.05, 0.35 + Math.sin(time * 0.7) * 0.18, -0.04);
        client.position.y = Math.sin(time * 1.1) * 0.05;
        shield.rotation.y = Math.sin(time * 0.6) * 0.3;
        shield.position.y = Math.sin(time * 1.1 + 1) * 0.05;
        house.rotation.y = -0.45 + Math.sin(time * 0.65) * 0.15;
        house.position.y = -0.02 + Math.sin(time * 1.1 + 2) * 0.05;
        [client, shield, house].forEach((obj, i) => {
            shadows[i].material.uniforms.uStrength.value = 0.42 - obj.position.y * 0.8;
        });

        // Timeline: pay -> held in escrow -> released to the maid.
        const t = (time % cycle) / cycle;
        let held = 0;
        if (t < 0.3) {
            setStep(0);
            const k = easeInOut(t / 0.3);
            coin.position.copy(curveA.getPoint(k));
            coin.scale.setScalar(Math.min(1, t / 0.04));
            coin.rotation.y += dt * 6;
        } else if (t < 0.62) {
            setStep(1);
            const k = (t - 0.3) / 0.32;
            held = Math.sin(Math.min(1, k * 1.2) * Math.PI * 0.5) * (1 - Math.max(0, (k - 0.85) / 0.15));
            coin.position.set(0, 0.1 + Math.sin(time * 3) * 0.02, 0.45 - easeOut(Math.min(1, k * 2)) * 0.15);
            coin.scale.setScalar(1 - 0.35 * Math.sin(Math.min(1, k) * Math.PI));
            coin.rotation.y += dt * 2.5;
        } else if (t < 0.9) {
            setStep(2);
            const k = easeInOut((t - 0.62) / 0.28);
            coin.position.copy(curveB.getPoint(k));
            coin.scale.setScalar(1);
            coin.rotation.y += dt * 6;
        } else {
            setStep(2);
            coin.scale.setScalar(Math.max(0.001, 1 - (t - 0.9) / 0.06));
        }

        const pulse = (ring, start) => {
            const p = (t - start) / 0.16;
            if (p >= 0 && p <= 1) {
                ring.scale.setScalar(0.5 + easeOut(p) * 0.7);
                ring.material.opacity = (1 - p) * 0.7;
            } else {
                ring.material.opacity = 0;
            }
        };
        pulse(shieldRing, 0.3);
        pulse(houseRing, 0.9);

        glow.intensity = held * 6;
        shield.scale.setScalar(1 + held * 0.05);
        windowGlow.emissiveIntensity = 0.35 + (t > 0.9 ? (1 - (t - 0.9) / 0.1) * 1.4 : 0);

        renderer.render(scene, camera);
        host.classList.add('is-3d');
    };

    stage.start();
}

/* ------------------------------------------------------------------ */
/* Gallery: curved 3D carousel                                          */
/* ------------------------------------------------------------------ */

function galleryScene(THREE, host) {
    const items = Array.from(host.querySelectorAll('[data-gallery-item]')).map((el) => ({
        src: el.getAttribute('data-src') || (el.querySelector('img') || {}).src,
        title: el.getAttribute('data-title') || '',
    })).filter((item) => item.src);
    if (items.length < 3) return;

    const viewport = host.querySelector('[data-gallery-stage]');
    const caption = host.querySelector('[data-gallery-caption]');
    const counter = host.querySelector('[data-gallery-counter]');
    const progress = host.querySelector('[data-gallery-progress]');
    const prevBtn = host.querySelector('[data-gallery-prev]');
    const nextBtn = host.querySelector('[data-gallery-next]');
    if (!viewport) return;

    const stage = createStage(THREE, host, { className: 'three-canvas--gallery' });
    viewport.appendChild(stage.canvas);

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(34, 1, 0.1, 80);

    const count = items.length;
    const cardW = 1.8;
    const cardH = 1.2;
    const gap = 0.28;
    const radius = Math.max(3.0, (count * (cardW + gap)) / (Math.PI * 2));
    const step = (Math.PI * 2) / count;
    const ring = new THREE.Group();
    scene.add(ring);

    // Bend a plane onto the ring's cylinder so every card is gently curved.
    const cardGeo = new THREE.PlaneGeometry(cardW, cardH, 32, 1);
    const pos = cardGeo.attributes.position;
    for (let i = 0; i < pos.count; i++) {
        const x = pos.getX(i);
        const a = x / radius;
        pos.setXYZ(i, Math.sin(a) * radius, pos.getY(i), Math.cos(a) * radius);
    }
    pos.needsUpdate = true;
    cardGeo.computeVertexNormals();

    const cardVertex = /* glsl */`
        varying vec2 vUv;
        void main() {
            vUv = uv;
            gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
        }
    `;
    const cardFragment = /* glsl */`
        uniform sampler2D uMap;
        uniform float uHasMap;
        uniform float uImageAspect;
        uniform float uCardAspect;
        uniform float uShade;
        uniform float uFocus;
        uniform float uReflect;
        uniform float uTime;
        uniform vec3 uGold;
        varying vec2 vUv;
        float roundedBox(vec2 p, vec2 b, float r) {
            vec2 q = abs(p) - b + r;
            return length(max(q, 0.0)) + min(max(q.x, q.y), 0.0) - r;
        }
        void main() {
            vec2 p = (vUv - 0.5) * vec2(uCardAspect, 1.0);
            float d = roundedBox(p, vec2(uCardAspect * 0.5, 0.5), 0.06);
            float mask = 1.0 - smoothstep(-0.004, 0.0, d);
            vec2 scale = uCardAspect > uImageAspect
                ? vec2(1.0, uImageAspect / uCardAspect)
                : vec2(uCardAspect / uImageAspect, 1.0);
            float zoom = 1.0 + uFocus * 0.04;
            vec3 color = uHasMap > 0.5
                ? texture2D(uMap, (vUv - 0.5) * scale / zoom + 0.5).rgb
                : vec3(0.06, 0.3, 0.36);
            // Glossy sheen sweeping across the focused card.
            float sweep = smoothstep(0.12, 0.0, abs(vUv.x - vUv.y * 0.3 - fract(uTime * 0.12) * 1.6 + 0.3));
            color += sweep * 0.08 * uFocus;
            // Hairline gold edge that brightens on the focused card.
            float edge = smoothstep(-0.012, -0.004, d);
            color = mix(color, uGold, edge * (0.35 + 0.65 * uFocus));
            color *= uShade;
            float alpha = mask;
            if (uReflect > 0.5) alpha *= smoothstep(0.45, 1.0, vUv.y) * 0.18;
            if (alpha < 0.01) discard;
            gl_FragColor = vec4(color, alpha);
        }
    `;

    const time = { value: 0 };
    const goldU = { value: new THREE.Vector3(...GOLD) };
    const meshes = [];
    const cards = items.map((item, i) => {
        const shared = {
            uMap: { value: null },
            uHasMap: { value: 0 },
            uImageAspect: { value: 1.5 },
            uCardAspect: { value: cardW / cardH },
            uFocus: { value: 0 },
            uTime: time,
            uGold: goldU,
        };
        const material = new THREE.ShaderMaterial({
            uniforms: { ...shared, uShade: { value: 1 }, uReflect: { value: 0 } },
            vertexShader: cardVertex,
            fragmentShader: cardFragment,
            transparent: true,
            side: THREE.DoubleSide,
        });
        const reflectionMaterial = new THREE.ShaderMaterial({
            uniforms: { ...shared, uShade: material.uniforms.uShade, uReflect: { value: 1 } },
            vertexShader: cardVertex,
            fragmentShader: cardFragment,
            transparent: true,
            depthWrite: false,
            side: THREE.DoubleSide,
        });

        const holder = new THREE.Group();
        holder.rotation.y = i * step;
        const card = new THREE.Mesh(cardGeo, material);
        card.userData.index = i;
        holder.add(card);
        meshes.push(card);
        const reflection = new THREE.Mesh(cardGeo, reflectionMaterial);
        reflection.scale.y = -1;
        reflection.position.y = -cardH - 0.08;
        holder.add(reflection);
        ring.add(holder);

        loadTexture(THREE, item.src).then((texture) => {
            shared.uMap.value = texture;
            shared.uImageAspect.value = texture.userData.aspect;
            shared.uHasMap.value = 1;
        }).catch(() => {});

        return { holder, card, uniforms: material.uniforms, focus: 0 };
    });

    const floor = new THREE.Mesh(
        new THREE.CircleGeometry(radius * 1.3, 64),
        new THREE.ShaderMaterial({
            transparent: true,
            depthWrite: false,
            vertexShader: 'varying vec2 vUv; void main(){ vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0); }',
            fragmentShader: 'varying vec2 vUv; void main(){ float d = length(vUv - vec2(0.5, 0.8)) * 2.2; gl_FragColor = vec4(0.89, 0.66, 0.34, (1.0 - smoothstep(0.0, 1.0, d)) * 0.08); }',
        })
    );
    floor.rotation.x = -Math.PI / 2;
    floor.position.y = -cardH / 2 - 0.05;
    scene.add(floor);

    stage.resize = (w, h) => {
        camera.aspect = w / h;
        const tan = Math.tan((camera.fov * Math.PI) / 360);
        const fitWidth = (cardW * (isSmall ? 1.25 : 2.1)) / (2 * tan * camera.aspect);
        const fitHeight = (cardH * 1.75) / (2 * tan);
        camera.userData.distance = Math.max(fitWidth, fitHeight);
        camera.updateProjectionMatrix();
    };

    /* Interaction: drag/swipe with snapping, arrows, keys, click a card, autoplay. */
    let rotation = 0;
    let target = 0;
    let dragging = false;
    let moved = 0;
    let lastX = 0;
    let velocity = 0;
    let lastInteraction = -10;
    let hovering = false;
    let clock = 0;
    const canvas = stage.canvas;
    canvas.style.touchAction = 'pan-y';

    const indexFor = (rot) => ((Math.round(-rot / step) % count) + count) % count;
    const goTo = (index) => {
        const current = indexFor(target);
        let delta = index - current;
        if (delta > count / 2) delta -= count;
        if (delta < -count / 2) delta += count;
        target -= delta * step;
        lastInteraction = clock;
    };
    const go = (dir) => {
        target = Math.round(target / step) * step - dir * step;
        lastInteraction = clock;
    };

    if (prevBtn) prevBtn.addEventListener('click', () => go(-1));
    if (nextBtn) nextBtn.addEventListener('click', () => go(1));
    host.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') { event.preventDefault(); go(-1); }
        if (event.key === 'ArrowRight') { event.preventDefault(); go(1); }
    });
    host.addEventListener('pointerenter', () => { hovering = true; });
    host.addEventListener('pointerleave', () => { hovering = false; });

    const raycaster = new THREE.Raycaster();
    const ndc = new THREE.Vector2();

    canvas.addEventListener('pointerdown', (event) => {
        dragging = true;
        moved = 0;
        lastX = event.clientX;
        velocity = 0;
        canvas.setPointerCapture(event.pointerId);
        host.classList.add('is-dragging');
    });
    canvas.addEventListener('pointermove', (event) => {
        if (!dragging) return;
        const dx = event.clientX - lastX;
        lastX = event.clientX;
        moved += Math.abs(dx);
        const delta = (dx / Math.max(1, stage.width)) * Math.PI * 1.2;
        rotation += delta;
        target = rotation;
        velocity = damp(velocity, delta * 60, 20, 1 / 60);
        lastInteraction = clock;
    });
    const release = (event) => {
        if (!dragging) return;
        dragging = false;
        host.classList.remove('is-dragging');
        if (moved < 6 && event && event.type === 'pointerup') {
            const rect = canvas.getBoundingClientRect();
            ndc.set(((event.clientX - rect.left) / rect.width) * 2 - 1, -((event.clientY - rect.top) / rect.height) * 2 + 1);
            raycaster.setFromCamera(ndc, camera);
            const hit = raycaster.intersectObjects(meshes, false)[0];
            if (hit) goTo(hit.object.userData.index);
            return;
        }
        // Throw with momentum, then settle on the nearest card.
        target = Math.round((rotation + velocity * 0.18) / step) * step;
    };
    canvas.addEventListener('pointerup', release);
    canvas.addEventListener('pointercancel', release);

    let front = -1;
    const autoplayEvery = 4.5;
    let autoTimer = 0;

    stage.frame = (dt, t) => {
        clock = t;
        time.value = t;

        if (!dragging) {
            rotation = damp(rotation, target, 5.5, dt);
            const idle = t - lastInteraction > 6 && !hovering;
            autoTimer = idle ? autoTimer + dt : 0;
            if (autoTimer > autoplayEvery) {
                autoTimer = 0;
                target = Math.round(target / step) * step - step;
            }
            if (progress) progress.style.transform = 'scaleX(' + Math.min(1, autoTimer / autoplayEvery).toFixed(3) + ')';
        }
        ring.rotation.y = rotation;

        const dist = camera.userData.distance || 6;
        camera.position.set(0, 0.42, radius + dist);
        camera.lookAt(0, -0.05, radius - 1.0);

        const idx = indexFor(rotation);
        cards.forEach((card, i) => {
            const angle = i * step + rotation;
            const facing = Math.cos(angle); // 1 = front, -1 = back
            card.uniforms.uShade.value = 0.35 + 0.65 * Math.pow(facing * 0.5 + 0.5, 1.4);
            card.focus = damp(card.focus, i === idx ? 1 : 0, 6, dt);
            card.uniforms.uFocus.value = card.focus;
            const s = 1 + card.focus * 0.06;
            card.card.scale.set(s, s, 1);
            card.card.position.z = card.focus * 0.12;
            card.holder.position.y = Math.sin(t * 0.9 + i) * 0.02;
        });

        if (idx !== front) {
            front = idx;
            if (caption) caption.textContent = items[front].title;
            if (counter) counter.textContent = String(front + 1).padStart(2, '0') + ' / ' + String(count).padStart(2, '0');
        }

        stage.renderer.render(scene, camera);
        host.classList.add('is-3d');
    };

    stage.start();
}
