<template>
    <span 
        class="svg-icon" 
        :class="[sizeClass, colorClass]"
        :style="customStyle"
        v-html="svgContent"
    ></span>
</template>

<script>
import { defineComponent, ref, computed, watch, onMounted } from 'vue'

// Import all SVG files from resources/svg directory
const svgModules = import.meta.glob('@svg/*.svg', { 
    query: '?raw',
    import: 'default',
    eager: false 
})

export default defineComponent({
    name: 'SvgIcon',
    
    props: {
        // Name of the SVG file (without .svg extension)
        name: {
            type: String,
            required: true
        },
        // Predefined sizes: xs, sm, md, lg, xl, 2xl
        size: {
            type: String,
            default: 'md',
            validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl', '2xl'].includes(value)
        },
        // Custom width (overrides size)
        width: {
            type: [String, Number],
            default: null
        },
        // Custom height (overrides size)
        height: {
            type: [String, Number],
            default: null
        },
        // Color using CSS variable name or hex/rgb value
        color: {
            type: String,
            default: 'currentColor'
        }
    },

    setup(props) {
        const svgContent = ref('')

        const loadSvg = async () => {
            const path = `/resources/svg/${props.name}.svg`
            const loader = svgModules[path]
            
            if (loader) {
                try {
                    const content = await loader()
                    // Remove width/height from SVG to allow CSS sizing
                    // Replace hardcoded fill and stroke colors with currentColor
                    svgContent.value = content
                        .replace(/width="[^"]*"/g, '')
                        .replace(/height="[^"]*"/g, '')
                        .replace(/<svg/, '<svg width="100%" height="100%"')
                        // Replace hardcoded fill colors with currentColor (including hex colors)
                        .replace(/fill="#[0-9a-fA-F]{3,6}"/gi, 'fill="currentColor"')
                        .replace(/fill="(?!none|white|currentColor)[^"]*"/gi, 'fill="currentColor"')
                        // Replace hardcoded stroke colors with currentColor (including hex colors)
                        .replace(/stroke="#[0-9a-fA-F]{3,6}"/gi, 'stroke="currentColor"')
                        .replace(/stroke="(?!none|currentColor)[^"]*"/gi, 'stroke="currentColor"')
                        // Remove background rectangles that are white
                        .replace(/<rect[^>]*fill="white"[^>]*><\/rect>/gi, '')
                } catch (error) {
                    console.error(`Failed to load SVG: ${props.name}`, error)
                    svgContent.value = ''
                }
            } else {
                console.warn(`SVG not found: ${props.name}. Available: ${Object.keys(svgModules).join(', ')}`)
                svgContent.value = ''
            }
        }

        const sizeClass = computed(() => `svg-icon--${props.size}`)

        const colorClass = computed(() => {
            // Check if using a predefined color variable
            if (props.color.startsWith('--')) {
                return ''
            }
            return ''
        })

        const customStyle = computed(() => {
            const style = {}
            
            // Custom dimensions
            if (props.width) {
                style.width = typeof props.width === 'number' ? `${props.width}px` : props.width
            }
            if (props.height) {
                style.height = typeof props.height === 'number' ? `${props.height}px` : props.height
            }
            
            // Color
            if (props.color.startsWith('--')) {
                style.color = `var(${props.color})`
            } else {
                style.color = props.color
            }
            
            return style
        })

        onMounted(() => {
            loadSvg()
        })

        watch(() => props.name, () => {
            loadSvg()
        })

        return {
            svgContent,
            sizeClass,
            colorClass,
            customStyle
        }
    }
})
</script>

<style>
.svg-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    vertical-align: middle;
    fill: currentColor;
    stroke: currentColor;
}

.svg-icon :deep(svg) {
    display: block;
    fill: currentColor;
    stroke: currentColor;
}

.svg-icon :deep(svg path),
.svg-icon :deep(svg rect),
.svg-icon :deep(svg circle),
.svg-icon :deep(svg polygon),
.svg-icon :deep(svg polyline),
.svg-icon :deep(svg line),
.svg-icon :deep(svg ellipse) {
    fill: inherit;
    stroke: inherit;
}

/* Predefined sizes */
.svg-icon--xs {
    width: 12px;
    height: 12px;
}

.svg-icon--sm {
    width: 16px;
    height: 16px;
}

.svg-icon--md {
    width: 20px;
    height: 20px;
}

.svg-icon--lg {
    width: 24px;
    height: 24px;
}

.svg-icon--xl {
    width: 32px;
    height: 32px;
}

.svg-icon--2xl {
    width: 48px;
    height: 48px;
}
</style>

