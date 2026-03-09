<script setup lang="ts">
  import { onMounted, onBeforeUnmount, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import api from '@/services/api'
  import { authStore } from '@/stores/auth'
  import * as dhx from 'dhx-suite'
  import * as XLSX from 'xlsx'
  import { Layout as dhxLayout } from 'dhx-suite'
  import 'dhx-suite/codebase/suite.min.css'

  type ClassRow = {
    id: number
    classNum: string
    number: number
    enrollmentYear: string
    graduationYear: string
    status: '未毕业' | '已毕业'
    displayName: string
  }

  type StudentCard = {
    id: string
    name: string
    studentId: string
    lastName: string
    firstName: string
    gender: string
    age: string
    img: string
    selected: boolean
  }

  const router = useRouter()
  const layoutContainer = ref<HTMLElement>()

  let layout: any = null
  let classToolbar: any = null
  let classGrid: any = null
  let studentToolbar: any = null
  let studentDataview: any = null
  let highlightedClassRowId: string | number | null = null
  let studentsCache: StudentCard[] = []

  const errorMessage = ref<string>('')
  const successMessage = ref<string>('')
  const isStudentLoading = ref<boolean>(false)
  const selectedClassName = ref<string>('')
  const selectedClassId = ref<number | null>(null)
  const selectedStudentId = ref<string>('')
  const FALLBACK_AVATAR = 'https://snippet.dhtmlx.com/codebase/data/common/img/02/avatar_01.jpg'
  const MIN_STUDENT_LOADING_MS = 1000
  const STUDENT_TEMPLATE_HEADERS = ['学号', '姓', '名', '性别', '年龄', '头像URL'] as const

  const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms))

  const exportWorkbook = (rows: Array<Record<string, string | number | null>>, fileName: string) => {
    const worksheet = XLSX.utils.json_to_sheet(rows, { header: [...STUDENT_TEMPLATE_HEADERS] })
    const workbook = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(workbook, worksheet, '学生模板')
    XLSX.writeFile(workbook, fileName)
  }

  const exportStudentTemplateWorkbook = (fileName: string) => {
    const aoa: Array<Array<string | number>> = [
      [...STUDENT_TEMPLATE_HEADERS],
      ['004', '王', '小明', '男', 10, 'https://example.com/avatar.jpg'],
    ]

    // Precreate blank rows so users can type directly in text-formatted 学号 cells.
    for (let i = 0; i < 200; i += 1) {
      aoa.push(['', '', '', '', '', ''])
    }

    const worksheet = XLSX.utils.aoa_to_sheet(aoa)

    for (let row = 2; row <= 202; row += 1) {
      const cellRef = XLSX.utils.encode_cell({ c: 0, r: row - 1 })
      const existing = worksheet[cellRef]
      worksheet[cellRef] = {
        ...(existing || { v: '' }),
        t: 's',
        z: '@',
      }
    }

    worksheet['!cols'] = [
      { wch: 16 },
      { wch: 10 },
      { wch: 10 },
      { wch: 10 },
      { wch: 10 },
      { wch: 36 },
    ]

    const workbook = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(workbook, worksheet, '学生模板')
    XLSX.writeFile(workbook, fileName)
  }

  const normalizeGender = (value: string) => {
    const gender = String(value || '').trim()
    if (['男', 'male', 'm', '1'].includes(gender.toLowerCase())) return '男'
    if (['女', 'female', 'f', '0'].includes(gender.toLowerCase())) return '女'
    return ''
  }

  const openFormDialog = (
    title: string,
    rows: any[],
    values: Record<string, any> = {},
    width = 520,
    height = 420,
  ): Promise<Record<string, any> | null> => {
    return new Promise((resolve) => {
      const host = document.createElement('div')
      const modalWindow = new (dhx as any).Window({
        title,
        width,
        height,
        modal: true,
        movable: true,
        closable: true,
        resizable: false,
      })

      const form = new (dhx as any).Form(host, {
        css: 'dhx_widget--bordered',
        padding: 16,
        rows: [
          ...rows,
          {
            cols: [
              { type: 'spacer' },
              { type: 'button', name: 'cancel', text: '取消', view: 'link' },
              { type: 'button', name: 'submit', text: '保存', color: 'primary' },
            ],
          },
        ],
      })

      form.setValue(values)
      modalWindow.attach(form)
      modalWindow.show()

      let resolved = false

      const safeResolve = (payload: Record<string, any> | null) => {
        if (resolved) return
        resolved = true
        form.destructor()
        modalWindow.destructor()
        resolve(payload)
      }

      form.events.on('click', (name: string) => {
        if (name === 'cancel') {
          safeResolve(null)
        }
        if (name === 'submit') {
          safeResolve(form.getValue())
        }
      })

      modalWindow.events.on('beforeHide', () => {
        safeResolve(null)
        return true
      })
    })
  }

  const classGetPriority = (value: string) => {
    if (!value) return ''

    let status = ''
    if (value === '未毕业') status = 'dhx-demo_grid-status--done'
    if (value === '已毕业') status = 'dhx-demo_grid-status--not-started'

    return `
    <div class='dhx-demo_grid-template'>
      <div class='dhx-demo_grid-status ${status}'></div>
      <span>${value}</span>
    </div>
  `
  }

  const normalize = (item: any): ClassRow => {
    const enrollmentYear = item.enrollment_year ? String(item.enrollment_year).trim() : ''
    const classNum = item.class_num ? String(item.class_num) : ''
    const displayName = (enrollmentYear && enrollmentYear !== '0') ? `${enrollmentYear}级${classNum}班` : classNum

    return {
      id: item.class_id,
      classNum,
      displayName,
      number: Number(item.student_count || 0),
      enrollmentYear,
      graduationYear: item.graduation_year ? String(item.graduation_year) : '',
      status: item.is_graduated ? '已毕业' : '未毕业',
    }
  }

  const toPayload = (item: ClassRow) => ({
    class_num: item.classNum,
    enrollment_year: item.enrollmentYear ? Number(item.enrollmentYear) : null,
    graduation_year: item.graduationYear ? Number(item.graduationYear) : null,
    is_graduated: item.status === '已毕业',
  })

  const studentInformationTemplate = ({ name, studentId, gender, age, img, selected }: StudentCard) => {
    const selectedClass = selected ? ' dhx_dataview_template_d--selected' : ''
    return `
    <div class="dhx_dataview_template_d${selectedClass}">
      <div class="dhx_dataview_template_d__inside">
        <div class="dhx_dataview_template_d__picture" style="background-image: url(${img});"></div>
        <div class="dhx_dataview_template_d__body">
          <span class="dhx_dataview_template_d__title">${name}</span>
          <div class="dhx_dataview_template_d__row">
            <span class="dhx_dataview_template_d__text">学号：${studentId}</span>
          </div>
          <div class="dhx_dataview_template_d__row">
            <span class="dhx_dataview_template_d__text">性别：${gender || '未知'}</span>
          </div>
          <div class="dhx_dataview_template_d__row">
            <span class="dhx_dataview_template_d__text">年龄：${age || '未设置'}</span>
          </div>
        </div>
      </div>
    </div>
  `
  }

  const normalizeStudent = (item: any): StudentCard => ({
    id: String(item.student_id),
    name: `${item.last_name || ''}${item.first_name || ''}` || '未命名学生',
    studentId: item.student_id,
    lastName: item.last_name || '',
    firstName: item.first_name || '',
    gender: item.gender || '',
    age: item.age ? String(item.age) : '',
    img: item.avatar_path || FALLBACK_AVATAR,
    selected: false,
  })

  const clearStudents = () => {
    if (classGrid && highlightedClassRowId !== null) {
      classGrid.removeRowCss(highlightedClassRowId, 'selected-class-row')
      highlightedClassRowId = null
    }
    selectedClassName.value = ''
    selectedClassId.value = null
    selectedStudentId.value = ''
    studentsCache = []
    if (studentToolbar) {
      studentToolbar.data.update('className', { value: '请选择班级' })
    }
    if (studentDataview) {
      studentDataview.data.parse([])
    }
  }

  const renderStudentViewData = () => {
    if (!studentDataview) return
    studentDataview.data.parse(studentsCache)
    if (selectedStudentId.value) {
      setSelectedStudentHighlight(selectedStudentId.value)
    }
    studentDataview.paint?.()
  }

  const setClassRowHighlight = (rowId: string | number) => {
    if (!classGrid) return

    if (highlightedClassRowId !== null && highlightedClassRowId !== rowId) {
      classGrid.removeRowCss(highlightedClassRowId, 'selected-class-row')
    }

    classGrid.addRowCss(rowId, 'selected-class-row')
    highlightedClassRowId = rowId
  }

  const setSelectedStudentHighlight = (studentId: string) => {
    if (!studentDataview) return

    const students = studentDataview.data.serialize() as StudentCard[]
    students.forEach((student) => {
      studentDataview.data.update(student.id, {
        selected: student.id === studentId,
      })
    })
  }

  const createStudentDataview = () => {
    const studentDataviewContainer = document.createElement('div')
    studentDataview = new (dhx as any).DataView(studentDataviewContainer, {
      css: 'dhx_dataview_template_d_box',
      gap: 8,
      itemsInRow: 4,
      selection: true,
      template: studentInformationTemplate,
    })

    studentDataview.events.on('click', (id: string) => {
      selectedStudentId.value = id
      setSelectedStudentHighlight(id)
    })
  }

  const loadStudentsByClass = async (classId: number) => {
    const startedAt = Date.now()
    isStudentLoading.value = true
    try {
      const response = await api.get(`/api/classes/${classId}/students`)
      studentsCache = (response.data?.students || []).map(normalizeStudent)
      selectedClassId.value = classId
      selectedStudentId.value = ''
      // 从API响应中构建完整的班级名称名称
      const classData = response.data?.class
      const enrollmentYear = classData?.enrollment_year ? String(classData.enrollment_year).trim() : ''
      const classNum = classData?.class_num ? String(classData.class_num) : ''
      selectedClassName.value = (enrollmentYear && enrollmentYear !== '0') ? `${enrollmentYear}级${classNum}班` : classNum
      if (studentToolbar) {
        studentToolbar.data.update('className', { value: `当前：${selectedClassName.value}` })
      }
      renderStudentViewData()
    } catch (error: any) {
      showError(error.response?.data?.message || '加载学生列表失败')
    } finally {
      const elapsed = Date.now() - startedAt
      if (elapsed < MIN_STUDENT_LOADING_MS) {
        await sleep(MIN_STUDENT_LOADING_MS - elapsed)
      }
      isStudentLoading.value = false
    }
  }

  const getSelectedStudent = (): StudentCard | null => {
    if (!selectedStudentId.value) return null
    return studentsCache.find((student) => student.id === selectedStudentId.value) || null
  }

  const createStudent = async () => {
    if (!selectedClassId.value) {
      showError('请选择一个班级')
      return
    }

    const values = await openFormDialog(
      '手动创建学生',
      [
        { type: 'input', name: 'studentId', label: '学号', labelPosition: 'left', required: true },
        { type: 'input', name: 'lastName', label: '姓', labelPosition: 'left', required: true },
        { type: 'input', name: 'firstName', label: '名', labelPosition: 'left', required: true },
        {
          type: 'select',
          name: 'gender',
          label: '性别',
          labelPosition: 'left',
          options: [
            { value: '', content: '未设置' },
            { value: '男', content: '男' },
            { value: '女', content: '女' },
          ],
        },
        { type: 'input', name: 'age', label: '年龄', labelPosition: 'left' },
        { type: 'input', name: 'avatarPath', label: '头像URL', labelPosition: 'left' },
      ],
      { studentId: '', lastName: '', firstName: '', gender: '', age: '', avatarPath: '' },
      560,
      440,
    )
    if (!values) return

    const studentId = String(values.studentId || '').trim()
    const lastName = String(values.lastName || '').trim()
    const firstName = String(values.firstName || '').trim()
    const gender = values.gender ? String(values.gender) : null
    const age = values.age ? String(values.age).trim() : ''
    const avatarPath = values.avatarPath ? String(values.avatarPath).trim() : ''

    if (!studentId || !lastName || !firstName) {
      showError('学号、姓、名为必填项')
      return
    }

    try {
      await api.post('/api/students', {
        student_id: studentId,
        class_id: selectedClassId.value,
        last_name: lastName,
        first_name: firstName,
        gender,
        age: age ? Number(age) : null,
        avatar_path: avatarPath || null,
      })

      showSuccess('学生创建成功')
      await loadClasses()
      await loadStudentsByClass(selectedClassId.value)
    } catch (error: any) {
      if (error.response?.data?.errors) {
        const errors = error.response.data.errors as Record<string, string[]>
        const key = Object.keys(errors)[0] || ''
        showError((key && errors[key]?.[0]) || '创建学生失败')
        return
      }
      showError(error.response?.data?.message || '创建学生失败')
    }
  }

  const editStudent = async () => {
    const student = getSelectedStudent()
    if (!student) {
      showError('请先在右侧选择一个学生')
      return
    }

    const values = await openFormDialog(
      `编辑学生：${student.name}`,
      [
        { type: 'input', name: 'studentId', label: '学号', labelPosition: 'left', disabled: true },
        { type: 'input', name: 'lastName', label: '姓', labelPosition: 'left', required: true },
        { type: 'input', name: 'firstName', label: '名', labelPosition: 'left', required: true },
        {
          type: 'select',
          name: 'gender',
          label: '性别',
          labelPosition: 'left',
          options: [
            { value: '', content: '未设置' },
            { value: '男', content: '男' },
            { value: '女', content: '女' },
          ],
        },
        { type: 'input', name: 'age', label: '年龄', labelPosition: 'left' },
        { type: 'input', name: 'avatarPath', label: '头像URL', labelPosition: 'left' },
      ],
      {
        studentId: student.studentId,
        lastName: student.lastName,
        firstName: student.firstName,
        gender: student.gender || '',
        age: student.age || '',
        avatarPath: student.img === FALLBACK_AVATAR ? '' : student.img,
      },
      560,
      440,
    )
    if (!values) return

    const lastName = String(values.lastName || '').trim()
    const firstName = String(values.firstName || '').trim()
    const gender = values.gender ? String(values.gender) : null
    const age = values.age ? String(values.age).trim() : ''
    const avatarPath = values.avatarPath ? String(values.avatarPath).trim() : ''

    if (!lastName || !firstName) {
      showError('姓、名为必填项')
      return
    }

    try {
      await api.put(`/api/students/${student.studentId}`, {
        last_name: lastName,
        first_name: firstName,
        gender,
        age: age ? Number(age) : null,
        avatar_path: avatarPath || null,
      })

      showSuccess('学生信息更新成功')
      if (selectedClassId.value) {
        await loadStudentsByClass(selectedClassId.value)
      }
    } catch (error: any) {
      if (error.response?.data?.errors) {
        const errors = error.response.data.errors as Record<string, string[]>
        const key = Object.keys(errors)[0] || ''
        showError((key && errors[key]?.[0]) || '更新学生失败')
        return
      }
      showError(error.response?.data?.message || '更新学生失败')
    }
  }

  const deleteStudent = async () => {
    const student = getSelectedStudent()
    if (!student) {
      showError('请先选择一个学生')
      return
    }

    dhx.confirm({
      header: '确认删除学生',
      text: `确定删除学生“${student.name}（${student.studentId}）”吗？`,
      buttons: ['取消', '确认删除'],
      buttonsAlignment: 'center',
    }).then(async (result) => {
      if (!result) return

      try {
        await api.delete(`/api/students/${student.studentId}`)
        showSuccess('学生已删除')
        if (selectedClassId.value) {
          await loadClasses()
          await loadStudentsByClass(selectedClassId.value)
        }
      } catch (error: any) {
        showError(error.response?.data?.message || '删除学生失败')
      }
    })
  }

  const parseCsvToStudents = (text: string) => {
    const lines = text.split(/\r?\n/).filter((line) => line.trim().length > 0)
    if (lines.length < 2) return []

    const headerLine = lines[0]
    if (!headerLine) return []
    const headers = headerLine.split(',').map((h) => h.trim())
    const idx = {
      student_id: headers.findIndex((h) => ['student_id', '学号'].includes(h)),
      last_name: headers.findIndex((h) => ['last_name', '姓'].includes(h)),
      first_name: headers.findIndex((h) => ['first_name', '名'].includes(h)),
      gender: headers.findIndex((h) => ['gender', '性别'].includes(h)),
      age: headers.findIndex((h) => ['age', '年龄'].includes(h)),
      avatar_path: headers.findIndex((h) => ['avatar_path', '头像', '头像URL'].includes(h)),
    }

    const rows: Array<Record<string, any>> = []
    for (let i = 1; i < lines.length; i += 1) {
      const line = lines[i]
      if (!line) continue
      const cols = line.split(',').map((c) => c.trim())
      if (!cols[idx.student_id] || !cols[idx.last_name] || !cols[idx.first_name]) continue
      rows.push({
        student_id: cols[idx.student_id],
        last_name: cols[idx.last_name],
        first_name: cols[idx.first_name],
        gender: idx.gender >= 0 ? cols[idx.gender] || null : null,
        age: idx.age >= 0 && cols[idx.age] ? Number(cols[idx.age]) : null,
        avatar_path: idx.avatar_path >= 0 ? cols[idx.avatar_path] || null : null,
      })
    }
    return rows
  }

  const parseXlsxToStudents = (file: File) => {
    const promise = new Promise<Array<Record<string, any>>>((resolve, reject) => {
      const reader = new FileReader()

      reader.onload = () => {
        try {
          const arrayBuffer = reader.result as ArrayBuffer
          const workbook = XLSX.read(arrayBuffer, { type: 'array' })
          const firstSheetName = workbook.SheetNames[0]
          if (!firstSheetName) {
            resolve([])
            return
          }

          const sheet = workbook.Sheets[firstSheetName]
          if (!sheet) {
            resolve([])
            return
          }
          const rows = XLSX.utils.sheet_to_json<Record<string, any>>(sheet, { defval: '', raw: false })
          const students = rows
            .map((row) => ({
              student_id: String(row['学号'] || row['student_id'] || '').trim(),
              last_name: String(row['姓'] || row['last_name'] || '').trim(),
              first_name: String(row['名'] || row['first_name'] || '').trim(),
              gender: normalizeGender(String(row['性别'] || row['gender'] || '')) || null,
              age: row['年龄'] || row['age'] ? Number(row['年龄'] || row['age']) : null,
              avatar_path: String(row['头像URL'] || row['avatar_path'] || '').trim() || null,
            }))
            .filter((item) => item.student_id && item.last_name && item.first_name)

          resolve(students)
        } catch (error) {
          reject(error)
        }
      }

      reader.onerror = () => reject(new Error('读取Excel文件失败'))
      reader.readAsArrayBuffer(file)
    })

    return promise
  }

  const downloadStudentTemplate = () => {
    exportStudentTemplateWorkbook('学生导入模板.xlsx')
    showSuccess('模板已导出，请按模板填写后上传')
  }

  const downloadStudentsExcel = () => {
    if (!selectedClassId.value) {
      showError('请选择一个班级')
      return
    }

    const aoa: Array<Array<string | number>> = [
      ['学号', '姓', '名', '性别', '年龄', '全名'],
    ]

    studentsCache.forEach((student) => {
      aoa.push([
        student.studentId,
        student.lastName,
        student.firstName,
        student.gender || '',
        student.age || '',
        student.lastName + student.firstName,
      ])
    })

    const worksheet = XLSX.utils.aoa_to_sheet(aoa)
    worksheet['!cols'] = [
      { wch: 16 },
      { wch: 10 },
      { wch: 10 },
      { wch: 16 },
      { wch: 10 },
      { wch: 10 },
    ]

    const workbook = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(workbook, worksheet, '学生信息')

    const fileName = `${selectedClassName.value}学生信息.xlsx`
    XLSX.writeFile(workbook, fileName)
    showSuccess('学生Excel已导出')
  }

  const uploadStudents = async () => {
    if (!selectedClassId.value) {
      showError('请选择一个班级')
      return
    }

    const input = document.createElement('input')
    input.type = 'file'
    input.accept = '.xlsx,.csv,text/csv'

    input.onchange = async () => {
      const file = input.files?.[0]
      if (!file) return

      let students: Array<Record<string, any>> = []
      if (file.name.toLowerCase().endsWith('.xlsx')) {
        try {
          students = await parseXlsxToStudents(file)
        } catch {
          showError('Excel 解析失败，请使用标准模板并检查内容格式')
          return
        }
      } else {
        const text = await file.text()
        students = parseCsvToStudents(text)
      }

      if (students.length === 0) {
        showError('CSV 无有效数据，请检查列名和内容')
        return
      }

      // 过滤掉与本班已有学生重复的学号
      const existingStudentIds = new Set(studentsCache.map((s) => s.studentId))
      const filteredStudents = students.filter((s) => !existingStudentIds.has(s.student_id))
      const duplicateCount = students.length - filteredStudents.length

      if (filteredStudents.length === 0) {
        showError(`所有学号都已存在，未添加任何学生`)
        return
      }

      try {
        await api.post('/api/students/bulk', {
          class_id: selectedClassId.value,
          students: filteredStudents,
        })

        let message = `批量导入成功（${filteredStudents.length}人）`
        if (duplicateCount > 0) {
          message += `，跳过重复学号${duplicateCount}条`
        }
        showSuccess(message)
        await loadClasses()
        if (selectedClassId.value) {
          await loadStudentsByClass(selectedClassId.value)
        }
      } catch (error: any) {
        if (error.response?.data?.duplicated_student_ids?.length) {
          // 如果服务端仍有重复（如并发操作），则再次过滤并重试
          const serverDuplicateIds = new Set(error.response.data.duplicated_student_ids)
          const retryStudents = filteredStudents.filter((s) => !serverDuplicateIds.has(s.student_id))

          if (retryStudents.length === 0) {
            showError('所有学号都已存在，未添加任何学生')
            return
          }

          try {
            await api.post('/api/students/bulk', {
              class_id: selectedClassId.value,
              students: retryStudents,
            })

            const totalDuplicateCount = duplicateCount + error.response.data.duplicated_student_ids.length
            let message = `批量导入成功（${retryStudents.length}人）`
            if (totalDuplicateCount > 0) {
              message += `，跳过重复学号${totalDuplicateCount}条`
            }
            showSuccess(message)
            await loadClasses()
            if (selectedClassId.value) {
              await loadStudentsByClass(selectedClassId.value)
            }
          } catch (retryError: any) {
            showError(retryError.response?.data?.message || '批量导入失败')
          }
        } else {
          showError(error.response?.data?.message || '批量导入失败')
        }
      }
    }

    input.click()
  }

  const loadClasses = async () => {
    try {
      const response = await api.get('/api/classes')
      const rows = (response.data?.classes || []).map(normalize)

      if (classGrid) {
        classGrid.data.parse(rows)
      }
    } catch (error: any) {
      showError(error.response?.data?.message || '加载班级失败')
    }
  }

  const createClass = async () => {
    const values = await openFormDialog(
      '新建班级',
      [
        { type: 'input', name: 'classNum', label: '班级号(01-99)', labelPosition: 'left', required: true },
        { type: 'input', name: 'enrollmentYear', label: '入学年份(1900-9999)', labelPosition: 'left' },
        { type: 'input', name: 'graduationYear', label: '毕业年份(1900-9999)', labelPosition: 'left' },
        {
          type: 'select',
          name: 'status',
          label: '状态',
          labelPosition: 'left',
          options: [
            { value: '未毕业', content: '未毕业' },
            { value: '已毕业', content: '已毕业' },
          ],
        },
      ],
      { classNum: '', enrollmentYear: '', graduationYear: '', status: '未毕业' },
      560,
      420,
    )
    if (!values) return

    const classNum = String(values.classNum || '').trim()
    if (!classNum) {
      showError('班级号不能为空')
      return
    }

    try {
      await api.post('/api/classes', {
        class_num: classNum,
        enrollment_year: values.enrollmentYear ? Number(values.enrollmentYear) : null,
        graduation_year: values.graduationYear ? Number(values.graduationYear) : null,
        is_graduated: values.status === '已毕业',
      })

      showSuccess('班级创建成功')
      await loadClasses()
    } catch (error: any) {
      showError(error.response?.data?.message || '班级创建失败')
    }
  }

  const editSelectedClass = async () => {
    if (!classGrid) return

    const selectedId = classGrid.selection.getCell()?.row?.id
    if (!selectedId) {
      showError('请选择一个班级')
      return
    }

    const row = classGrid.data.getItem(selectedId) as ClassRow
    const values = await openFormDialog(
      `编辑班级：${row.displayName}`,
      [
        { type: 'input', name: 'classNum', label: '班级号(01-99)', labelPosition: 'left', required: true },
        { type: 'input', name: 'enrollmentYear', label: '入学年份(1900-9999)', labelPosition: 'left' },
        { type: 'input', name: 'graduationYear', label: '毕业年份(1900-9999)', labelPosition: 'left' },
        {
          type: 'select',
          name: 'status',
          label: '状态',
          labelPosition: 'left',
          options: [
            { value: '未毕业', content: '未毕业' },
            { value: '已毕业', content: '已毕业' },
          ],
        },
      ],
      {
        classNum: row.classNum,
        enrollmentYear: row.enrollmentYear,
        graduationYear: row.graduationYear,
        status: row.status,
      },
      560,
      420,
    )
    if (!values) return

    const classNum = String(values.classNum || '').trim()
    if (!classNum) {
      showError('班级号不能为空')
      return
    }

    const nextRow: ClassRow = {
      ...row,
      classNum,
      enrollmentYear: values.enrollmentYear ? String(values.enrollmentYear).trim() : '',
      graduationYear: values.graduationYear ? String(values.graduationYear).trim() : '',
      status: values.status === '已毕业' ? '已毕业' : '未毕业',
      displayName: values.enrollmentYear ? `${values.enrollmentYear}级${classNum}班` : classNum,
    }

    try {
      await api.put(`/api/classes/${row.id}`, toPayload(nextRow))
      showSuccess('班级更新成功')
      await loadClasses()
    } catch (error: any) {
      if (error.response?.data?.errors) {
        const errors = error.response.data.errors as Record<string, string[]>
        const key = Object.keys(errors)[0] || ''
        if (key && errors[key]?.[0]) {
          showError(errors[key][0])
        } else {
          showError('班级更新失败')
        }
      } else {
        showError(error.response?.data?.message || '班级更新失败')
      }
    }
  }

  const deleteSelectedClass = async () => {
    if (!classGrid) return

    const selectedId = classGrid.selection.getCell()?.row?.id
    if (!selectedId) {
      showError('请选择一个班级')
      return
    }

    const row = classGrid.data.getItem(selectedId) as ClassRow

    dhx.confirm({
      header: '确认删除班级',
      text: `确定删除"${row.displayName}"吗？关联学生及测验分配会被级联删除。`,
      buttons: ['取消', '确认删除'],
      buttonsAlignment: 'center',
    }).then(async (result) => {
      if (!result) return

      try {
        await api.delete(`/api/classes/${row.id}`)
        showSuccess('班级已删除')
        clearStudents()
        await loadClasses()
      } catch (error: any) {
        showError(error.response?.data?.message || '删除班级失败')
      }
    })
  }

  onMounted(async () => {
    if (!authStore.isAuthenticated()) {
      router.push('/login')
      return
    }

    await api.get('/sanctum/csrf-cookie')

    const rootEl = layoutContainer.value
    if (rootEl) {
      layout = new dhxLayout(rootEl, {
        cols: [
          {
            id: 'classColumn',
            width: '40%',
            rows: [
              { id: 'classToolbar', height: 56 },
              { id: 'classGrid' },
            ],
          },
          {
            id: 'studentColumn',
            width: '60%',
            rows: [
              { id: 'studentToolbar', height: 56 },
              { id: 'studentContent' },
            ],
          },
        ],
        css: 'dhx_layout_cell--overflow-auto class-layout',
        type: 'line',
      })

      const toolbarContainer = document.createElement('div')
      classToolbar = new (dhx as any).Toolbar(toolbarContainer, {
        css: 'app-class-toolbar',
        data: [
          { type: 'spacer' },
          { id: 'create', type: 'button', value: '新建班级' },
          { id: 'edit', type: 'button', value: '编辑班级' },
          { id: 'delete', type: 'button', value: '删除班级' },
        ],
      })

      const gridContainer = document.createElement('div')
      classGrid = new (dhx as any).Grid(gridContainer, {
        columns: [
          {
            id: 'displayName',
            header: [
              { text: '班级名称', align: 'center' },
              { content: 'comboFilter', tooltipTemplate: () => '选择一个班级' },
            ],
            align: 'center',
            resizable: true,
          },
          {
            id: 'number',
            header: [{ text: '学生人数', align: 'center' }, { content: 'inputFilter' }],
            align: 'center',
          },
          {
            id: 'enrollmentYear',
            header: [{ text: '入学年份 (级)', align: 'center' }, { content: 'inputFilter' }],
            align: 'center',
          },
          {
            id: 'graduationYear',
            header: [{ text: '毕业年份 (届)', align: 'center' }, { content: 'inputFilter' }],
            align: 'center',
          },
          {
            id: 'status',
            header: [{ text: '状态', align: 'center' }, { content: 'selectFilter' }],
            align: 'center',
            editorType: 'combobox',
            options: ['未毕业', '已毕业'],
            editorConfig: {
              template: ({ value }: any) => classGetPriority(value),
            },
            template: classGetPriority,
            htmlEnable: true,
          },
        ],
        autoWidth: true,
        height: 'auto',
        selection: 'row',
        editable: false,
        dragItem: 'both',
        keyNavigation: true,
        leftSplit: 1,
      })

      const studentToolbarContainer = document.createElement('div')
      studentToolbar = new (dhx as any).Toolbar(studentToolbarContainer, {
        css: 'app-student-toolbar',
        data: [
          { id: 'className', type: 'text', value: '请选择左侧班级' },
          { type: 'spacer' },
          { id: 'downloadTemplate', type: 'button', value: '下载导入模板' },
          { id: 'createStudent', type: 'button', value: '手动添加' },
          { id: 'uploadStudents', type: 'button', value: '批量上传' },
          { id: 'editStudent', type: 'button', value: '编辑学生' },
          { id: 'deleteStudent', type: 'button', value: '删除学生' },
          { id: 'refresh', type: 'button', value: '刷新信息' },
          { id: 'downloadStudentsExcel', type: 'button', value: '导出到Excel' },
        ],
      })

      createStudentDataview()

      layout.getCell('classToolbar').attach(classToolbar)
      layout.getCell('classGrid').attach(classGrid)
      layout.getCell('studentToolbar').attach(studentToolbar)
      layout.getCell('studentContent').attach(studentDataview)

      classToolbar.events.on('click', (id: string) => {
        if (id === 'create') createClass()
        if (id === 'edit') editSelectedClass()
        if (id === 'delete') deleteSelectedClass()
      })

      studentToolbar.events.on('click', (id: string) => {
        if (id === 'downloadTemplate') downloadStudentTemplate()
        if (id === 'createStudent') createStudent()
        if (id === 'uploadStudents') uploadStudents()
        if (id === 'downloadStudentsExcel') downloadStudentsExcel()
        if (id === 'editStudent') editStudent()
        if (id === 'deleteStudent') deleteStudent()
        if (id === 'refresh') {
          const selectedId = classGrid.selection.getCell()?.row?.id
          if (!selectedId) {
            showError('请选择一个班级')
            return
          }
          loadStudentsByClass(Number(selectedId))
        }
      })

      classGrid.events.on('cellClick', (row: any) => {
        const classId = Number(row?.id)
        const rowData = classGrid.data.getItem(row?.id)
        if (!classId || !rowData) return

        setClassRowHighlight(row.id)
        selectedClassId.value = classId
        // 不在这里设置selectedClassName，让loadStudentsByClass从API获取最新的完整班级名称
        loadStudentsByClass(classId)
      })

      await loadClasses()
      clearStudents()
    }
  })

  onBeforeUnmount(() => {
    if (studentDataview) studentDataview.destructor()
    if (studentToolbar) studentToolbar.destructor()
    if (classGrid) classGrid.destructor()
    if (classToolbar) classToolbar.destructor()
    if (layout) layout.destructor()
  })

  const showSuccess = (message: string) => {
    successMessage.value = message
    errorMessage.value = ''
    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  }

  const showError = (message: string) => {
    errorMessage.value = message
    successMessage.value = ''
  }
</script>

<template>
  <div class="class-view-wrapper">
    <div class="page-header">
      <h1>班级管理</h1>
      <div class="header-alerts">
        <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>
        <div v-if="errorMessage" class="alert alert-error">{{ errorMessage }}</div>
      </div>
    </div>

    <div class="layout-shell">
      <div ref="layoutContainer" class="layout-container"></div>
      <div v-if="isStudentLoading" class="student-loading-overlay student-loading-overlay--student-content">
        <div class="student-loading-spinner"></div>
        <span>正在加载学生数据...</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
  .class-view-wrapper {
    width: 100%;
    height: 100%;
    flex: 1;
    display: flex;
    flex-direction: column;
    background-color: var(--color-background);
  }

  .page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 20px;
    background: var(--color-background-soft);
    border-bottom: 1px solid var(--color-border);
  }

  .page-header h1 {
    margin: 0;
    color: var(--color-heading);
    font-size: 24px;
  }

  .header-alerts {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 10px;
    margin-left: auto;
  }

  .alert {
    padding: 8px 12px;
    white-space: nowrap;
    border-radius: 6px;
    font-size: 14px;
  }

  .alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
  }

  .alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }

  .layout-container {
    flex: 1;
    width: 100%;
    height: 100%;
    min-height: 0;
    overflow: hidden;
  }

  .layout-shell {
    position: relative;
    display: flex;
    flex-direction: column;
    flex: 1;
    width: 100%;
    height: 100%;
    min-height: 0;
  }

  .student-loading-overlay {
    position: absolute;
    top: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.72);
    backdrop-filter: blur(1px);
    z-index: 20;
    color: #334155;
    font-size: 14px;
  }

  .student-loading-overlay--student-content {
    top: 56px;
    left: 40%;
    right: 0;
  }

  .student-loading-spinner {
    width: 26px;
    height: 26px;
    border: 3px solid #cbd5e1;
    border-top-color: #0ea5e9;
    border-radius: 50%;
    animation: student-spin 0.8s linear infinite;
  }

  @keyframes student-spin {
    from {
      transform: rotate(0deg);
    }

    to {
      transform: rotate(360deg);
    }
  }

  :deep(.app-class-toolbar) {
    border-bottom: 1px solid var(--color-border);
  }

  :deep(.app-student-toolbar) {
    border-bottom: 1px solid var(--color-border);
  }

  :deep(.dhx-demo_grid-template) {
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  :deep(.dhx-demo_grid-status) {
    width: 8px;
    height: 8px;
    border-radius: 50%;
  }

  :deep(.dhx-demo_grid-status--done) {
    background-color: var(--dhx-color-success);
  }

  :deep(.dhx-demo_grid-status--not-started) {
    background-color: var(--dhx-color-danger);
  }

  :deep(.selected-class-row) {
    background: #dcfce7;
  }

  :deep(.dhx_dataview_template_d_box) {
    padding: 8px;
  }

  :deep(.dhx_dataview_template_d_box .dhx_dataview-item) {
    background: transparent !important;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    padding: 0 !important;
  }

  :deep(.dhx_dataview_template_d_box .dhx_dataview-item--selected) {
    background: transparent !important;
  }

  :deep(.dhx_dataview_template_d) {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    transition: background-color 0.2s ease, border-color 0.2s ease;
    min-height: 88px;
  }

  :deep(.dhx_dataview_template_d--selected) {
    background: #dcfce7;
    border-color: #22c55e;
  }

  :deep(.dhx_dataview_template_d__inside) {
    display: flex;
    gap: 8px;
    align-items: center;
    padding: 10px 10px 10px 8px;
  }

  :deep(.dhx_dataview_template_d__picture) {
    width: 50px;
    height: 70px;
    background-size: cover;
    background-position: center top;
    border-radius: 4px;
    flex-shrink: 0;
    border: 1px solid #cbd5e1;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.12);
  }

  :deep(.dhx_dataview_template_d__body) {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 4px;
    min-width: 0;
  }

  :deep(.dhx_dataview_template_d__title) {
    font-weight: 600;
    color: #0f172a;
    font-size: 14px;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  :deep(.dhx_dataview_template_d__row) {
    line-height: 1.25;
    color: #334155;
    font-size: 12px;
  }

  :deep(.dhx_dataview_template_d__text) {
    color: #475569;
  }

  :deep(.dhx_dataview_template_d__status) {
    width: 6px;
    border-radius: 4px;
    background: #94a3b8;
  }

  :deep(.dhx_dataview_template_d__status--check) {
    background: #16a34a;
  }

  :deep(.dhx_dataview_template_d__status--uncheck) {
    background: #94a3b8;
  }
</style>
