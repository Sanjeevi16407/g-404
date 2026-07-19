import { Renderer, Program, Mesh, Triangle } from 'https://cdn.jsdelivr.net/npm/ogl';

class LineWavesBackground {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;

        this.speed = 0.3;
        this.innerLineCount = 32.0;
        this.outerLineCount = 36.0;
        this.warpIntensity = 1.0;
        this.rotation = -45;
        this.edgeFadeWidth = 0.0;
        this.colorCycleSpeed = 1.0;
        this.brightness = 0.25;
        this.color1 = '#ffffff';
        this.color2 = '#ffffff';
        this.color3 = '#ffffff';
        this.enableMouseInteraction = true;
        this.mouseInfluence = 2.0;

        this.init();
    }

    hexToVec3(hex) {
        const h = hex.replace('#', '');
        return [
            parseInt(h.slice(0, 2), 16) / 255,
            parseInt(h.slice(2, 4), 16) / 255,
            parseInt(h.slice(4, 6), 16) / 255
        ];
    }

    init() {
        this.renderer = new Renderer({ alpha: true, premultipliedAlpha: false });
        this.gl = this.renderer.gl;
        this.gl.clearColor(0, 0, 0, 0);

        this.currentMouse = [0.5, 0.5];
        this.targetMouse = [0.5, 0.5];

        this.handleMouseMove = (e) => {
            const rect = this.gl.canvas.getBoundingClientRect();
            this.targetMouse = [
                (e.clientX - rect.left) / rect.width,
                1.0 - (e.clientY - rect.top) / rect.height
            ];
        };

        this.handleMouseLeave = () => {
            this.targetMouse = [0.5, 0.5];
        };

        this.resize = () => {
            if (!this.container) return;
            this.renderer.setSize(this.container.offsetWidth, this.container.offsetHeight);
            if (this.program) {
                this.program.uniforms.uResolution.value = [this.gl.canvas.width, this.gl.canvas.height, this.gl.canvas.width / this.gl.canvas.height];
            }
        };

        window.addEventListener('resize', this.resize);
        this.resize();

        const geometry = new Triangle(this.gl);
        const rotationRad = (this.rotation * Math.PI) / 180;

        const vertexShader = `
        attribute vec2 uv;
        attribute vec2 position;
        varying vec2 vUv;
        void main() {
          vUv = uv;
          gl_Position = vec4(position, 0, 1);
        }
        `;

        const fragmentShader = `
        precision highp float;

        uniform float uTime;
        uniform vec3 uResolution;
        uniform float uSpeed;
        uniform float uInnerLines;
        uniform float uOuterLines;
        uniform float uWarpIntensity;
        uniform float uRotation;
        uniform float uEdgeFadeWidth;
        uniform float uColorCycleSpeed;
        uniform float uBrightness;
        uniform vec3 uColor1;
        uniform vec3 uColor2;
        uniform vec3 uColor3;
        uniform vec2 uMouse;
        uniform float uMouseInfluence;
        uniform bool uEnableMouse;

        #define HALF_PI 1.5707963

        float hashF(float n) {
          return fract(sin(n * 127.1) * 43758.5453123);
        }

        float smoothNoise(float x) {
          float i = floor(x);
          float f = fract(x);
          float u = f * f * (3.0 - 2.0 * f);
          return mix(hashF(i), hashF(i + 1.0), u);
        }

        float displaceA(float coord, float t) {
          float result = sin(coord * 2.123) * 0.2;
          result += sin(coord * 3.234 + t * 4.345) * 0.1;
          result += sin(coord * 0.589 + t * 0.934) * 0.5;
          return result;
        }

        float displaceB(float coord, float t) {
          float result = sin(coord * 1.345) * 0.3;
          result += sin(coord * 2.734 + t * 3.345) * 0.2;
          result += sin(coord * 0.189 + t * 0.934) * 0.3;
          return result;
        }

        vec2 rotate2D(vec2 p, float angle) {
          float c = cos(angle);
          float s = sin(angle);
          return vec2(p.x * c - p.y * s, p.x * s + p.y * c);
        }

        void main() {
          vec2 coords = gl_FragCoord.xy / uResolution.xy;
          coords = coords * 2.0 - 1.0;
          coords = rotate2D(coords, uRotation);

          float halfT = uTime * uSpeed * 0.5;
          float fullT = uTime * uSpeed;

          float mouseWarp = 0.0;
          if (uEnableMouse) {
            vec2 mPos = rotate2D(uMouse * 2.0 - 1.0, uRotation);
            float mDist = length(coords - mPos);
            mouseWarp = uMouseInfluence * exp(-mDist * mDist * 4.0);
          }

          float warpAx = coords.x + displaceA(coords.y, halfT) * uWarpIntensity + mouseWarp;
          float warpAy = coords.y - displaceA(coords.x * cos(fullT) * 1.235, halfT) * uWarpIntensity;
          float warpBx = coords.x + displaceB(coords.y, halfT) * uWarpIntensity + mouseWarp;
          float warpBy = coords.y - displaceB(coords.x * sin(fullT) * 1.235, halfT) * uWarpIntensity;

          vec2 fieldA = vec2(warpAx, warpAy);
          vec2 fieldB = vec2(warpBx, warpBy);
          vec2 blended = mix(fieldA, fieldB, mix(fieldA, fieldB, 0.5));

          float fadeTop = smoothstep(uEdgeFadeWidth, uEdgeFadeWidth + 0.4, blended.y);
          float fadeBottom = smoothstep(-uEdgeFadeWidth, -(uEdgeFadeWidth + 0.4), blended.y);
          float vMask = 1.0 - max(fadeTop, fadeBottom);

          float tileCount = mix(uOuterLines, uInnerLines, vMask);
          float scaledY = blended.y * tileCount;
          float nY = smoothNoise(abs(scaledY));

          float ridge = pow(
            step(abs(nY - blended.x) * 2.0, HALF_PI) * cos(2.0 * (nY - blended.x)),
            5.0
          );

          float lines = 0.0;
          for (float i = 1.0; i < 3.0; i += 1.0) {
            lines += pow(max(fract(scaledY), fract(-scaledY)), i * 2.0);
          }

          float pattern = vMask * lines;

          float cycleT = fullT * uColorCycleSpeed;
          float rChannel = (pattern + lines * ridge) * (cos(blended.y + cycleT * 0.234) * 0.5 + 1.0);
          float gChannel = (pattern + vMask * ridge) * (sin(blended.x + cycleT * 1.745) * 0.5 + 1.0);
          float bChannel = (pattern + lines * ridge) * (cos(blended.x + cycleT * 0.534) * 0.5 + 1.0);

          vec3 col = (rChannel * uColor1 + gChannel * uColor2 + bChannel * uColor3) * uBrightness;
          float alpha = clamp(length(col), 0.0, 1.0);

          gl_FragColor = vec4(col, alpha);
        }
        `;

        this.program = new Program(this.gl, {
            vertex: vertexShader,
            fragment: fragmentShader,
            uniforms: {
                uTime: { value: 0 },
                uResolution: { value: [this.gl.canvas.width, this.gl.canvas.height, this.gl.canvas.width / this.gl.canvas.height] },
                uSpeed: { value: this.speed },
                uInnerLines: { value: this.innerLineCount },
                uOuterLines: { value: this.outerLineCount },
                uWarpIntensity: { value: this.warpIntensity },
                uRotation: { value: rotationRad },
                uEdgeFadeWidth: { value: this.edgeFadeWidth },
                uColorCycleSpeed: { value: this.colorCycleSpeed },
                uBrightness: { value: this.brightness },
                uColor1: { value: this.hexToVec3(this.color1) },
                uColor2: { value: this.hexToVec3(this.color2) },
                uColor3: { value: this.hexToVec3(this.color3) },
                uMouse: { value: new Float32Array([0.5, 0.5]) },
                uMouseInfluence: { value: this.mouseInfluence },
                uEnableMouse: { value: this.enableMouseInteraction }
            }
        });

        this.mesh = new Mesh(this.gl, { geometry, program: this.program });
        this.container.appendChild(this.gl.canvas);

        if (this.enableMouseInteraction) {
            window.addEventListener('mousemove', this.handleMouseMove);
        }

        const updateFrame = (time) => {
            this.animationFrameId = requestAnimationFrame(updateFrame);
            this.program.uniforms.uTime.value = time * 0.001;

            if (this.enableMouseInteraction) {
                this.currentMouse[0] += 0.05 * (this.targetMouse[0] - this.currentMouse[0]);
                this.currentMouse[1] += 0.05 * (this.targetMouse[1] - this.currentMouse[1]);
                this.program.uniforms.uMouse.value[0] = this.currentMouse[0];
                this.program.uniforms.uMouse.value[1] = this.currentMouse[1];
            }

            this.renderer.render({ scene: this.mesh });
        };

        this.animationFrameId = requestAnimationFrame(updateFrame);
    }

    destroy() {
        if (this.animationFrameId) {
            cancelAnimationFrame(this.animationFrameId);
        }
        window.removeEventListener('resize', this.resize);
        window.removeEventListener('mousemove', this.handleMouseMove);
        if (this.gl && this.gl.canvas && this.container && this.container.contains(this.gl.canvas)) {
            this.container.removeChild(this.gl.canvas);
        }
        this.gl?.getExtension('WEBGL_lose_context')?.loseContext();
    }
}

window.LineWavesBackground = LineWavesBackground;
