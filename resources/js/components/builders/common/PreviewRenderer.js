const PreviewRenderer = {
    name: 'PreviewRenderer',
    props: ['element'],
    template: `
        <!-- Layouts -->
        <div v-if="element.type === 'header'" class="preview-block header-block">
            <PreviewRenderer v-for="(child, i) in element.children" :key="i" :element="child" />
        </div>

        <div v-if="element.type === 'main'" class="preview-block main-block">
            <PreviewRenderer v-for="(child, i) in element.children" :key="i" :element="child" />
        </div>

        <div v-if="element.type === 'footer'" class="preview-block footer-block">
            <PreviewRenderer v-for="(child, i) in element.children" :key="i" :element="child" />
        </div>

        <div v-if="element.type === 'section'" class="preview-block section-block" style="padding: 20px; border: 1px dashed #ccc; margin: 10px 0;">
            <PreviewRenderer v-for="(child, i) in element.children" :key="i" :element="child" />
            <div v-if="!element.children?.length" class="empty-preview">Empty Section</div>
        </div>
        
        <div v-if="element.type === 'columns'" class="preview-block columns-block" style="display: flex; gap: 20px;">
             <PreviewRenderer v-for="(child, i) in element.children" :key="i" :element="child" />
        </div>

        <div v-if="element.type === 'column'" class="preview-block column-block" style="flex: 1; padding: 10px; border: 1px dotted #ccc;">
             <PreviewRenderer v-for="(child, i) in element.children" :key="i" :element="child" />
        </div>

        <!-- Content -->
        <h1 v-if="element.type === 'h1'">{{ element.content || 'Heading 1' }}</h1>
        <h2 v-if="element.type === 'h2'">{{ element.content || 'Heading 2' }}</h2>
        <h3 v-if="element.type === 'h3'">{{ element.content || 'Heading 3' }}</h3>
        <p v-if="element.type === 'p'">{{ element.content || 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.' }}</p>
        <div v-if="element.type === 'image'" style="width: 100%; height: 150px; background: #eee; display: flex; align-items: center; justify-content: center; color: #888; overflow: hidden;">
             <img v-if="element.src" :src="element.src" style="width:100%; height:100%; object-fit:cover;" />
             <span v-else>Image Preview</span>
        </div>
    `
};

// Handle recursion
PreviewRenderer.components = { PreviewRenderer };

export default PreviewRenderer;
